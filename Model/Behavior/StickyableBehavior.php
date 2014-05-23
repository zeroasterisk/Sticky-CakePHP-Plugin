<?php
/**
 * Search conditions for CakeDC Searchable plugin may need to dynamically
 * create Joins or Contains statements...
 *
 * But if they do, those statements are not injectable at a 'conditions' level.
 *
 * And if the $Model->contains() method is used, it DOES setup the correct contains
 * but only for the "next" query - not for any query using those conditions.
 *
 * So we have them setup a new set of sticky details
 * and those sticky details are used on every findquery, until cleared
 *
 * !!!IMPORTANT!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *   This behavior must be included/loaded BEFORE the Containable Behavior
 *     public $actsAs = array(
 *       'Stickyable',
 *       'Containable',
 *     );
 * !!!IMPORTANT!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 *  - addStickyContain((array)$contain) = (boolean)
 *  - addStickyJoin((array)$join) = (boolean)
 *  - getStickyContain() = (array)$contain
 *  - getStickyJoin() = (array)$join
 *  - clearStickyContain() = (boolean)
 *  - clearStickyJoin() = (boolean)
 *  - clearSticky() = (boolean)
 *  - disableSticky() = (boolean)
 *  - enableSticky() = (boolean)
 */
App::uses('ModelBehavior', 'Model');
class StickyableBehavior extends ModelBehavior {

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array(
				'joins' => [],
				'contain' => [],
				'enable' => true,
			);
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);
	}

	/**
	 * Runs before a find() operation.
	 *
	 * Used to inject 'contain' and 'joins' settings
	 *
	 * @param Model $Model Model using the behavior
	 * @param array $query Query parameters as set by cake
	 * @return array
	 */
	public function beforeFind(Model $Model, $query) {
		if (!$this->settings[$Model->alias]['enable']) {
			return $query;
		}
		$query = $this->beforeFindAddContain($Model, $query);
		$query = $this->beforeFindAddJoin($Model, $query);
		return $query;
	}

	/**
	 * Runs before a find() operation.
	 *
	 * Used to inject 'contain' and 'joins' settings
	 *
	 * @param Model $Model Model using the behavior
	 * @param array $query Query parameters as set by cake
	 * @return array
	 */
	public function beforeFindAddContain(Model $Model, $query) {
		if (isset($query['contain']) && $query['contain'] === false) {
			return $query;
		}
		if (empty($query['contain'])) {
			$query['contain'] = [];
		}
		$query['contain'] = array_merge(
			$this->getStickyContain($Model),
			(array)$query['contain']
		);
		return $query;
	}

	/**
	 * Runs before a find() operation.
	 *
	 * Used to inject 'contain' and 'joins' settings
	 *
	 * @param Model $Model Model using the behavior
	 * @param array $query Query parameters as set by cake
	 * @return array
	 */
	public function beforeFindAddJoin(Model $Model, $query) {
		if (isset($query['joins']) && $query['joins'] === false) {
			return $query;
		}
		if (empty($query['joins'])) {
			$query['joins'] = [];
		}
		$query['joins'] = array_merge(
			$this->getStickyJoin($Model),
			(array)$query['joins']
		);
		return $query;
	}

	/**
	 * adds a sticky contain setting
	 *
	 * @param Model $Model Model on which binding restriction is being applied
	 * @param array $contain config [any number of arguments]
	 * @return boolean
	 */
	public function addStickyContain(Model $Model) {
		$args = func_get_args();
		$contain = call_user_func_array('am', array_slice($args, 1));
		$this->settings[$Model->alias]['contain'] = array_merge(
			$this->settings[$Model->alias]['contain'],
			$contain
		);
		return true;
	}
	public function addStickyJoin(Model $Model) {
		$args = func_get_args();
		$joins = call_user_func_array('am', array_slice($args, 1));
		$this->settings[$Model->alias]['joins'] = array_merge(
			$this->settings[$Model->alias]['joins'],
			$joins
		);
		return true;
	}
	public function getStickyContain(Model $Model) {
		return $this->settings[$Model->alias]['contain'];
	}
	public function getStickyJoin(Model $Model) {
		return $this->settings[$Model->alias]['joins'];
	}
	public function clearStickyContain(Model $Model) {
		$this->settings[$Model->alias]['contain'] = [];
		return true;
	}
	public function clearStickyJoin(Model $Model) {
		$this->settings[$Model->alias]['joins'] = [];
		return true;
	}
	public function clearSticky(Model $Model) {
		$this->settings[$Model->alias]['contain'] = [];
		$this->settings[$Model->alias]['joins'] = [];
		return true;
	}
	public function disableSticky(Model $Model) {
		$this->settings[$Model->alias]['enable'] = false;
		return true;
	}
	public function enableSticky(Model $Model) {
		$this->settings[$Model->alias]['enable'] = true;
		return true;
	}
}
