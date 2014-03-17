<?php
namespace Web\Apps\Gallery;

/*******************************************************
 * Gallery App for smf web framework
 *
 * @author Michael Zorn
 * @license GPL
 * @version 0.8
 *******************************************************/

use Web\Framework\Lib\App;
use Web\Framework\Lib\Url;

// portal files loaded
define('Gallery_loaded', true);


final class Gallery extends App
{
	public $init = 'load';
	public $lang = true;
	public $css = true;
	public $hooks = array(
		'integrate_delete_member' => 'Web::App::Gallery::onMemberDelete',
	);
	public $perms = array(
		'perm' => array(
			'manage_album',
			'manage_image',
		)
	);

	public $config = array(

		// Group: Album
		'album_grid' => array(
			'group' => 'album',
			'control' => 'select',
			'data' => array('array', array(3,6,9,12)),
			'default' => 12
		),

		// Group: paths
		'file_uploadpath' => array(
			'group' => 'paths',
			'control' => 'input',
			'default' => '/Web/Uploads/images/Gallery'
		)
	);

	public $routes = array(
		'album_index' => array(
			'route' => '',
			'ctrl' => 'album',
			'action' => 'gallery'
		),
		'album_album' => array(
			'route' => '/[i:id_album]',
			'ctrl' => 'album',
			'action' => 'index'
		),
		'album_add' => array(
			'method' => 'GET|POST',
			'route' => '/add',
			'ctrl' => 'album',
			'action' => 'edit'
		),
		'album_edit' => array(
			'method' => 'GET|POST',
			'route' => '/[i:id_album]/edit',
			'ctrl' => 'album',
			'action' => 'edit'
		),
		'picture' => array(
			'route' => '/picture/[i:id_picture]',
			'ctrl' => 'picture',
			'action' => 'index'
		),
		'picture_random' => array(
			'route' => '/picture/random',
			'ctrl' => 'picture',
			'action' => 'random',
		),
	);

	protected function initPaths()
	{
		global $boarddir, $boardurl;

		parent::initPaths();

		// fileupload dir and url
		$this->Cfg('dir_gallery', $boarddir . $this->Cfg('file_uploadpath'));
		$this->Cfg('url_gallery', $boardurl . $this->Cfg('file_uploadpath'));
	}

	public function onBefore()
	{
		$html = '<div id="gallery">';
		return $html;
	}

	public function onAfter()
	{
		$html = '</div>';
		return $html;
	}

	/*
	 * Creates the arrayelements of Raidmanager menu.
	 */
	public function Menu(&$menu_buttons)
	{
		$url = Url::factory('gallery_album_index')->getUrl();

		$menu_buttons['gallery'] = array(
			'title' => 'Gallery',
			'href' => $url,
			'show' => true,
			'sub_buttons' => array()
		);

		unset($url);
	}

	/**
	 * Writes membername to the picture and gallery tables of the member to delete
	 * @param unknown $id_member
	 */
	public function onMemberDelete($id_member)
	{
		// Get the member name by member id
		$Model = App::create('Forum')->Model('Members');
		$Model->Find($id_member, array('member_name', 'real_name'));
		$member_name = $member->real_name ? $member->real_name : $member->member_name;

		// Create Gallery app
		$App = App::create('Gallery');

		// Update the albums of this member
		$Model = $App->Model('Album');
		$Model->setField('member_name');
		$Model->setFilter('id_member={int:id_member}');
		$Model->setParameter(array(
			'membername' => $member_name,
			'id_member' => $id_member
		));
		$Model->Update();

		// And then the pictures
		$Model = $App->Model('Picture');
		$Model->setField('member_name');
		$Model->setFilter('id_member={int:id_member}');
		$Model->setParameter(array(
			'membername' => $member_name,
			'id_member' => $id_member
		));
		$Model->Update();

		// Some cleanups
		unset($App, $Model);
	}
}
?>