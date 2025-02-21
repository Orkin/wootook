<?php

class Wootook_Core_View
    extends Wootook_Object
{
    protected $_template = null;
    protected $_partials = array();
    protected $_layout = null;
    protected $_nameInLayout = null;

    protected $_scriptPath = null;

    public function __construct(Array $data = array())
    {
        if (isset($data['template'])) {
            $this->_template = $data['template'];
            unset($data['template']);
        }

        parent::__construct($data);
    }

    protected function _prepareRender()
    {
        return $this;
    }

    public function renderNumber($number)
    {
        return Math::render($number);
    }

    public function renderTime($time, $unique = false)
    {
        if ($time >= 10) {
            $seconds = $time % 60;
            $minutes = (int) (($time - $seconds) / 60) % 60;
            $hours = (int) ((($time - $seconds) / 60) - $minutes) / 60;

            if ($hours > 24) {
                $dayHours = (int) $hours % 24;
                $days = (int) ($hours - $dayHours) / 24;

                return $this->__('%1$d day(s) and %2$d hour(s)', $days, $dayHours);
            } else if ($hours > 0) {
                return $this->__('%1$d hour(s), %2$d minute(s) and %3$d second(s)', $hours, $minutes, $seconds);
            } else if ($minutes > 0) {
                return $this->__('%1$d minute(s) and %2$d second(s)', $minutes, $seconds);
            } else {
                return $this->__('%1$d second(s)', $seconds);
            }
        } else if (!$unique && $time > 0) {
            return $this->__('%1$d per minute', 60 / $time);
        } else {
            return $this->__('instantaneous');
        }
    }

    public function escape($unescaped)
    {
        return htmlspecialchars($unescaped, ENT_QUOTES, 'UTF-8');
    }

    public function escapeJs($unescaped)
    {
        return addslashes($unescaped);
    }

    public function __($message, $_ = null)
    {
        $args = func_get_args();
        array_shift($args);

        return Wootook::translate(Wootook::getDefaultLocale(), $message, $args);
    }

    public function translate($message, $_ = null)
    {
        $args = func_get_args();
        array_shift($args);

        return Wootook::translate(Wootook::getLocale(), $message, $args);
    }

    public function renderScript($file)
    {
        $this->_prepareRender();

        $templatePath = $this->_getTemplatePath($file);
        if ($templatePath !== null) {
            ob_start();
            include $this->_getTemplatePath($file);
            $contents = ob_get_contents();
            ob_end_clean();

            return $contents;
        }
        return '';
    }

    protected function _getTemplatePath($file)
    {
        return $this->getScriptPath() . DIRECTORY_SEPARATOR . $file;
    }

    public function render()
    {
        $template = $this->getTemplate();
        if (empty($template)) {
            return null;
        }
        return $this->renderScript($template);
    }

    public function setTemplate($template)
    {
        $this->_template = $template;

        return $this;
    }

    public function getTemplate()
    {
        return $this->_template;
    }

    public function getUrl($uri, Array $params = array())
    {
        return Wootook::getUrl($uri, $params);
    }

    public function getSkinUrl($uri, Array $params = array(), $theme = null, $package = null)
    {
        $user = Wootook_Empire_Model_User::getSingleton();
        if ($user !== null && $user->getId()) {
            $theme = $user->getSkinPath();
        }
        if ($theme === null || empty($theme)) {
            $theme = $this->getLayout()->getTheme();
        }
        if ($theme === null || empty($theme)) {
            $theme = Wootook_Core_Layout::DEFAULT_THEME;
        }

        if ($package === null || empty($package)) {
            $package = $this->getLayout()->getPackage();
        }
        if ($package === null || empty($package)) {
            $package = Wootook_Core_Layout::DEFAULT_PACKAGE;
        }
        $domain = $this->getLayout()->getDomain();

        $baseUrl = Wootook::getBaseUrl('skin');
        $basePath = Wootook::getBasePath('skin');
        $pathPattern = $basePath . "{$domain}/%s/%s/{$uri}";
        $urlPattern = $baseUrl . "{$domain}/%s/%s/{$uri}";

        if (count($params) > 0) {
            $serializedParams = array();
            foreach ($params as $paramKey => $paramValue) {
                if ($paramValue) {
                    $serializedParams[] = "{$paramKey}={$paramValue}";
                }
            }

            $urlPattern .= '?' . implode('&', $serializedParams);
        }

        if (Wootook::fileExists(sprintf($pathPattern, $package, $theme))) {
            return sprintf($urlPattern, $package, $theme);
        }
        if (Wootook::fileExists(sprintf($pathPattern, $package, Wootook_Core_Layout::DEFAULT_THEME))) {
            return sprintf($urlPattern, $package, Wootook_Core_Layout::DEFAULT_THEME);
        }

        return sprintf($urlPattern, Wootook_Core_Layout::DEFAULT_PACKAGE, Wootook_Core_Layout::DEFAULT_THEME);
    }

    public function setPartial($name, $content)
    {
        if (!is_string($content) && !$content instanceof self) {
            return $this;
        }

        $this->_partials[$name] = $content;

        return $this;
    }

    public function getPartial($name)
    {
        if (!$this->hasPartial($name)) {
            return null;
        }
        return $this->_partials[$name];
    }

    public function getAllPartials()
    {
        return $this->_partials;
    }

    public function unsetPartial($name)
    {
        if (isset($this->_partials[$name])) {
            unset($this->_partials[$name]);
        }

        return $this;
    }

    public function hasPartial($name)
    {
        return isset($this->_partials[$name]) && (is_string($this->_partials[$name]) || $this->_partials[$name] instanceof self);
    }

    public function __set($name, $content)
    {
        return $this->setPartial($name, $content);
    }

    public function __get($name)
    {
        $name = str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));
        $name[0] = strtolower($name[0]);
        return $this->getPartial($name);
    }

    public function __unset($name)
    {
        return $this->unsetPartial($name);
    }

    public function __isset($name)
    {
        return $this->hasPartial($name);
    }

    public function setLayout($layout)
    {
        $this->_layout = $layout;

        return $this;
    }

    /**
     *
     * @return Wootook_Core_Layout
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    public function prepareLayout()
    {
    }

    public function beforeToHtml()
    {
    }

    public function setNameInLayout($name)
    {
        $this->_nameInLayout = $name;

        return $this;
    }

    public function getNameInLayout()
    {
        return $this->_nameInLayout;
    }

    public function setScriptPath($scriptPath)
    {
        $this->_scriptPath = $scriptPath;

        return $this;
    }

    public function getScriptPath()
    {
        return $this->_scriptPath;
    }
}