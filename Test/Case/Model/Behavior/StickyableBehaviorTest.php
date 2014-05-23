<?php
App::uses('StickyableBehavior', 'Sticky.Model/Behavior');
App::uses('AppModel', 'Model');


// this is our mockup Article model, from CakeCore
class Article extends CakeTestModel {
	public $recursive = -1;
	public $actsAs = array(
		'Sticky.Stickyable',
		'Containable',
	);
	public $belongsTo = array('User');
	public $hasMany = array('Comment' => array('dependent' => true));
	// funky contains: requires a conditions to really make sense
	public $hasOne = array(
		'MyComment' => array(
			'className' => 'Comment',
			'conditions' => array(
				//'Comment.user_id' => 3,
			),
			'dependent' => false,
		));
}
class Comment extends CakeTestModel {
	public $name = 'Comment';
	public $belongsTo = array('Article', 'User');
	public $hasOne = array('Attachment' => array('dependent' => true));
}

class StickyableBehaviorTest extends CakeTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 * @access public
	 */
	public $fixtures = array(
		'core.article', 'core.article_featured', 'core.article_featureds_tags',
		'core.articles_tag', 'core.attachment', 'core.category',
		'core.comment', 'core.featured', 'core.tag', 'core.user',
		'core.join_a', 'core.join_b', 'core.join_c', 'core.join_a_c', 'core.join_a_b'
	);

	public $StickyableBehavior;
	public $Model;

	/**
	 * Start Test callback
	 *
	 * @param string $method
	 * @return void
	 * @access public
	 */
	public function startTest($method) {
		parent::startTest($method);
		$this->StickyableBehavior = new StickyableBehavior();
		$this->Model = ClassRegistry::init('Article');
		/* this is only needed if not already setup on the Model (via AppModel) in the correct order
			$this->Model->Behaviors->detach('Containable');
			$this->Model->Behaviors->attach('Stickyable', array());
			$this->Model->Behaviors->attach('Containable', array());
		 */
	}

	/**
	 * End Test callback
	 *
	 * @param string $method
	 * @return void
	 * @access public
	 */
	public function endTest($method) {
		parent::endTest($method);
		unset($this->StickyableBehavior);
		unset($this->Model);
		ClassRegistry::flush();
	}

	public function testRealWorld() {
		// find all Articles on which I have commented
		//   we setup a hasOne() for MyComment (allowing a simple inner join)
		//   we setup a Sticky Contain with conditions

		// add a sticky contains
		$this->assertTrue($this->Model->addStickyContain(array(
			'MyComment' => array(
				'fields' => array('id', 'created'),
				'conditions' => array(
					'MyComment.user_id' => 2
				)
			),
		)));

		// prep options for a find requiring the sticky contains
		$options = array(
			'fields' => array('id', 'title'),
			'conditions' => array(
				'NOT' => array(
					'MyComment.id' => NULL
				),
			),
		);
		$expect = array(
			(int) 0 => array(
				'Article' => array(
					'id' => '1',
					'title' => 'First Article'
				),
				'MyComment' => array(
					'id' => '1',
					'created' => '2007-03-18 10:45:23'
				)
			),
			(int) 1 => array(
				'Article' => array(
					'id' => '2',
					'title' => 'Second Article'
				),
				'MyComment' => array(
					'id' => '6',
					'created' => '2007-03-18 10:55:23'
				)
			)
		);
		$this->assertEqual(
			$this->Model->find('all', $options),
			$expect
		);
		// works on multiple queries
		$this->assertEqual(
			$this->Model->find('all', $options),
			$expect
		);
		// works on multiple queries of different types too
		$this->assertEqual(
			$this->Model->find('count', $options),
			2
		);

		// now clear ouy sticky contains. should fail
		$this->assertTrue($this->Model->clearSticky());
		try {
			$result = $this->Model->find('all', $options);
			$this->assertEqual(
				'Expected an Exception but did not get it...',
				"'SQLSTATE[42S22]: Column not found: 1054 Unknown column 'MyComment.id' in 'where clause'"
			);
		} catch (Exception $e) {
			$this->assertPattern("#.*Unknown column 'MyComment.id' .*#", $e->getMessage());
		}
	}

	public function testAllStickyContain() {
		$this->assertTrue($this->Model->addStickyContain([
			'AnyContainAlias' => [
				'conditions' => [
					'AnyContainAlias.key' => 'any_key',
					'AnyContainAlias.value' => '3',
				]
			],
		]));
		$this->assertTrue($this->Model->addStickyContain([
			'MyFriend' => [
				'fields' => ['id', 'member_2_id'],
				'order' => ['status_id' => 'ASC'],
				'conditions' => [
					'member_1_id' => 1,
				]
			]
		]));
		$this->assertTrue($this->Model->addStickyContain([
			'MyFriend' => [
				'fields' => ['id'],
				'conditions' => [
					'member_1_id' => 10,
					'status_id' => 2,
				]
			]
		]));
		$this->assertEqual($this->Model->getStickyContain(), [
			'AnyContainAlias' => [
				'conditions' => [
					'AnyContainAlias.key' => 'any_key',
					'AnyContainAlias.value' => '3',
				]
			],
			'MyFriend' => [
				'fields' => ['id'],
				'conditions' => [
					'member_1_id' => 10,
					'status_id' => 2,
				]
			]
		]);
		$this->assertTrue($this->Model->clearStickyContain());
		$this->assertEqual($this->Model->getStickyContain(), []);
	}

	public function testAllStickyJoin() {
		$this->assertTrue($this->Model->addStickyJoin([
			[
				'table' => 'member_friends',
				'alias' => 'MemberFriend',
				'type' => 'LEFT',
				'conditions' => [
					'AND' =>  [
						'MemberFriend.member_2_id' => 10,
						'MemberFriend.member_1_id = Member.id',
					]
				]
			],
		]));
		$this->assertTrue($this->Model->addStickyJoin([
			[
				'table' => 'member_friends',
				'alias' => 'MemberFriend',
				'type' => 'LEFT',
				'conditions' => [
					'AND' =>  [
						'MemberFriend.member_2_id' => 9,
						'MemberFriend.member_1_id = Member.id',
					]
				]
			],
		]));
		$this->assertEqual($this->Model->getStickyJoin(), [
			[
				'table' => 'member_friends',
				'alias' => 'MemberFriend',
				'type' => 'LEFT',
				'conditions' => [
					'AND' =>  [
						'MemberFriend.member_2_id' => 10,
						'MemberFriend.member_1_id = Member.id',
					]
				]
			],
			[
				'table' => 'member_friends',
				'alias' => 'MemberFriend',
				'type' => 'LEFT',
				'conditions' => [
					'AND' =>  [
						'MemberFriend.member_2_id' => 9,
						'MemberFriend.member_1_id = Member.id',
					]
				]
			],
		]);
		$this->assertTrue($this->Model->clearStickyJoin());
		$this->assertEqual($this->Model->getStickyJoin(), []);
	}

}

