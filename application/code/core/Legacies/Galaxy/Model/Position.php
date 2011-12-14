<?php

/**
 *
 * Enter description here ...
 * @author Orkin
 *
 */
class Legacies_Galaxy_Model_Position extends Wootook_Core_Entity_SubTable {

	protected $_eventPrefix = 'galaxy.position';
	protected $_eventObject = 'position';

	/**
	 * @var Wootook_Empire_Model_Planet
	 */
	protected $_moon = null;

	/**
	 * @var Wootook_Empire_Model_Planet
	 */
	protected $_planet = null;
	
	/**
	 * @var Wootook_Empire_Model_User
	 */
	protected $_user = null;

	/**
	 * @var array
	 */
	protected static $_instances = array();

	/**
	 *
	 * Retourne l'instance correspondant � $key
	 * @param string $key
	 */
	public static function factory($key)
	{
		if (!isset(self::$_instances[$key])) {
			$instance = new self();
			$params = func_get_args();
			call_user_func_array(array($instance, 'load'), $params);
			self::$_instances[$key] = $instance;
		}
		return self::$_instances[$key];
	}
	
	/**
	 *
	 * retourne l'objet pr�sent aux coordonn�es donn�es
	 * @param string/int $galaxy
	 * @param string/int $system
	 * @param string/int $position
	 */
	public static function factoryFromCoords($galaxy, $system, $position)
	{

		if ($galaxy === null || $system === null || $position === null ) {
			return new self();
		}

		$galaxy = intval($galaxy);
		$system = intval($system);
		$position = intval($position);

		$coords = array(
                'galaxy'   => $galaxy,
                'system'   => $system,
                'position' => $position
		);

		$database = Wootook_Database::getSingleton();
		$collection = new Wootook_Core_Collection('galaxy');
		$collection
		->where('galaxy=:galaxy')
		->where('system=:system')
		->where('planet=:position')
		->limit(1)
		->load($coords);
			
		$galaxyData = $collection->getFirstItem();

		if ($galaxyData === null || !$galaxyData->getData('galaxy')) {
			return new self();
		}

		// Format la cl� de $_instances
		$key = sprintf('%d:%d:%d', $galaxy, $system, $position);

		return self::factory($key);
	}

	protected function _init()
	{
		$this->_tableName = 'galaxy';
		$this->_idFieldNames = array('galaxy', 'system', 'planet');
	}
	
		/**
	 *
	 * V�rifie si une planete existe pour les coordonn�es
	 * @return bool
	 */
	public function hasPlanet() {
		return (bool) ($this->getData('id_planet'));
	}

	/**
	 *
	 * Retourne la planete situ�e � la position, s'il n'y a pas de planete la methode retourne null
	 * @return Wootook_Empire_Model_Planet
	 */
	public function getPlanet() {
		
		// On v�rifie que qu'une planete existe bien sur l'instance
		if (!$this->hasPlanet()) {
			return null;
		}
		
		// On v�rifie que la planete n'a pas d�j� �t� charg�e
		if ($this->_planet === null) {
			
			// On r�cup�re la planete en fonction de son id
			$planet = new Wootook_Empire_Model_Planet();
			$planet->factory($this->getData('id_planet'));
			
			$this->_planet = $planet;
		}
		
		return $this->_planet;
	}

	/**
	 *
	 * Retourne la lune situ�e � la position, s'il n'y a pas de lune, la methode retourne null
	 * @return Wootook_Empire_Model_Planet
	 */
	public function getMoon() {

		// On v�rifie s'il y a bien une planete sur l'instance
		if ($this->getPlanet() === null) {
			return null;
		}

		// On r�cup�re la lune de la planete
		$moon = $this->getPlanet()->getMoon();
		
		// TODO je ne suis pas s�r que getMoon() retourne null donc par s�curit�
		if ($moon === null) {
			return null;
		}
				
		$this->_moon = $moon;
		
		return $this->_moon;
	}

	/**
	 *
	 * Retourne l'utilisateur � la position, si la planete n'est pas colonis�e la methode retourne null
	 * @return Wootook_Empire_Model_User
	 */
	public function getUser() {

		// On v�rifie s'il y a bien une planete sur l'instance
		if ($this->getPlanet() === null) {
			return null;
		}
		
		// On va r�cup�rer l'utilisateur � qui appartient la planete (ne peux pas �tre null puisqu'une planete appartient forc�ment � un utilisateur)
		$user = $this->getPlanet()->getUser();
		
		$this->_user = $user;
		
		return $this->_user;
	}

	/**
	 *
	 * Retourne l'alliance du joueur a qui appartient la planete � la position, si le joueur n'a pas d'alliance ou si la planete n'est pas colonis�, la m�thode retourne null
	 * @return Wootook_Alliance_Model_Entity
	 */
	public function getAlliance() {

	}

	/**
	 *
	 * Retourne la galaxy
	 */
	public function getGalaxy() {
		return (int) $this->getData('galaxy');
	}

	/**
	 * Modifie la galaxy
	 * @param string/int $galaxy
	 */
	public function setGalaxy($galaxy) {
		$this->setData('galaxy', $galaxy);

		return $this;
	}

	/**
	 *
	 * Retourne le system
	 */
	public function getSystem() {
		return (int) $this->getData('system');
	}

	/**
	 *
	 * Modifie le system
	 * @param string/int $system
	 */
	public function setSystem($system) {
		$this->setData('system', $system);

		return $this;
	}

	/**
	 *
	 * Retourne la position
	 */
	public function getPosition() {
		return (int) $this->getData('planet');
	}

	/**
	 *
	 * Modifie la position
	 * @param string/int $position
	 */
	public function setPosition($position) {
		$this->setData('planet', $position);

		return $this;
	}

	/**
	 *
	 * Retourne le d�bris li� au type pass� en param�tre
	 * @param string de type Legacies_Empire::RESOURCE_* $resourceType
	 */
	public function getDebrisAmount($resourceType) {
		$resource = Wootook_Empire_Model_Game_Resources::getSingleton();
		$resourceConfig = $resource->getData($resourceType);
		
		if ($resourceConfig === null) {
			return null;
		}
		
		return (int) ($this->getData($resourceConfig['field']));
	}

	/**
	 *
	 * Modifie la valeur du champ en fonction du type de retource pass� en param�tre
	 * @param string de type Legacies_Empire::RESOURCE_* $resourceType
	 * @param string/int $amount
	 */
	public function setDebrisAmount($resourceType, $amount) {
		$resource = Wootook_Empire_Model_Game_Resources::getSingleton();
		$resourceConfig = $resource->getData($resourceType);
		
		if ($resourceConfig === null) {
			return null;
		}
		$this->setData($resourceConfig('field'), $amount);

		return $this;
	}

}
