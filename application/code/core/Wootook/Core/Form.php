<?php

class Wootook_Core_Form
{
    protected $_elements = array();

    protected $_request = null;

    /**
     *
     * Element class loader
     * @var Wootook_Core_Form_ElementLoader
     */
    protected $_elementLoader = null;

    /**
     *
     * Validator class loader
     * @var Wootook_Core_Form_ValidatorLoader
     */
    protected $_validatorLoader = null;

    /**
     *
     * Enter description here ...
     * @var Wootook_Core_Model_Session
     */
    protected $_session = null;

    public function __construct(Wootook_Core_Model_Session $session, Array $elements = array())
    {
        $this->_session = $session;

        $this->_elementLoader = new Wootook_Core_Form_ElementLoader($this, array(
            'Wootook_Core_Form_Element_' => 'Wootook/Core/Form/Element'
            ));

        $this->_validatorLoader = new Wootook_Core_Form_ValidatorLoader($this, array(
            'Wootook_Core_Form_Validator_' => 'Wootook/Core/Form/Validator'
            ));

        $this->addElement('__formkey', 'text', array('form_key' => 'formKey'));

        foreach ($elements as $elementName => $elementConfig) {
            if (is_string($elementConfig)) {
                $this->addElement($elementName, $elementConfig);
            } else if ($elementConfig instanceof Wootook_Core_Form_ElementAbstract) {
                $this->addElement($elementName, $elementConfig);
            } else {
                if (isset($elementConfig['validators'])) {
                    $this->addElement($elementName, $elementConfig['type'], $elementConfig['validators']);
                } else {
                    $this->addElement($elementName, $elementConfig['type']);
                }
            }
        }
    }

    public function validate()
    {
        foreach ($this->_elements as $element) {
            if (!$element->validate()) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * Enter description here ...
     * @param string $name
     * @param string|Wootook_Core_Form_ElementAbstract $type
     * @return Wootook_Core_Form
     */
    public function addElement($name, $type = 'text', Array $validators = array())
    {
        if ($type instanceof Wootook_Core_Form_ElementAbstract) {
            $this->_elements[$name] = $name;
            $this->_elements[$name]->setForm($this);
        } else {
            $element = $this->_elementLoader->load($type);

            if ($element === null) {
                trigger_error(sprintf('Element %1$s (type: %2$s) could not be created.', $name, $type), E_USER_WARNING);
                return $this;
            }
            $this->_elements[$name] = $element;
        }
        $this->_elements[$name]->setName($name);

        foreach ($validators as $validatorName => $validatorType) {
            if ($validatorType instanceof Wootook_Core_Form_ElementAbstract) {
                $this->_elements[$name]->addValidator($validatorType, $validatorName);
            } else {
                $validator = $this->_validatorLoader->load($validatorType);

                if ($validator === null) {
                    Wootook_Core_ErrorProfiler::getSingleton()
                        ->addException(new Wootook_Core_Exception_RuntimeException(sprintf('Validator %1$s (type: %2$s) could not be created.', $validatorName, $validatorType)));
                    return $this;
                }
                $this->_elements[$name]->addValidator($validator, $validatorName);
            }
        }

        return $this;
    }

    /**
     *
     * Enter description here ...
     * @param string $name
     * @return Wootook_Core_Form_ElementAbstract
     */
    public function getElement($name)
    {
        if (!isset($this->_elements[$name])) {
            return null;
        }

        return $this->_elements[$name];
    }

    /**
     *
     * Enter description here ...
     * @param string $name
     * @return Wootook_Core_Model_Session
     */
    public function getSession()
    {
        return $this->_session;
    }

    public function setRequest(Wootook_Core_Controller_Request_Http $request)
    {
        $this->_request = $request;

        return $this;
    }

    /**
     *
     * Enter description here ...
     * @return Wootook_Core_Controller_Request_Http
     */
    public function getRequest()
    {
        return $this->_request;
    }

    public function getData()
    {
        $request = $this->getRequest();
        if ($request === null) {
            return array();
        }
        if ($request->isPost()) {
            return $request->getAllPostData();
        }
        return $request->getAllQueryData();
    }

    public function populate(Array $datas = array())
    {
        $request = $this->getRequest();

        foreach ($this->_elements as $elementName => $element) {
            if (isset($datas[$elementName])) {
                $element->populate($datas[$elementName]);
            } else if ($request->isPost()) {
                $element->populate($request->getPost($elementName));
            } else {
                $element->populate($request->getQuery($elementName));
            }
        }
    }
}