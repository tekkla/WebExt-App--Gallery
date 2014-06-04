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
use Web\Framework\Lib\FileIO;

// portal files loaded
define('Gallery_loaded', true);


final class Gallery extends App
{
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
		'grid' => array(
			'group' => 'display',
			'control' => 'select',
			'data' => array('array', array(2,3,4,6,12), 1),
			'default' => 4
		),

		// Group Image
		'thumbnail_use' => array(
			'group' => 'thumbnail',
			'control' => 'switch',
			'default' => 1
		),
		'thumbnail_width' => array(
			'group' => 'thumbnail',
			'control' => 'number',
			'default' => 640
		),
		'thumbnail_quality' => array(
			'group' => 'thumbnail',
			'control' => array('number', array('min' => 1, 'max' => 100)),
			'default' => 80
		),

		// Group: paths
		'path' => array(
			'group' => 'upload',
			'control' => 'input',
			'default' => '/Web/Uploads/images/Gallery'
		),
		'upload_mime_types' => array(
			'group' => 'upload',
			'control' => 'optiongroup',
			'data' => array(
				'array',
				array(
					'image/gif',
					'image/jpeg',
					'image/png',
				),
				1
			)
		),
		'upload_no_overwrite' => array(
			'group' => 'upload',
			'control' => 'switch',
			'default' => 0
		),
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
		'album_new' => array(
			'method' => 'GET|POST',
			'route' => '/new',
			'ctrl' => 'album',
			'action' => 'edit'
		),
		'album_edit' => array(
			'method' => 'GET|POST',
			'route' => '/[i:id_album]/edit',
			'ctrl' => 'album',
			'action' => 'edit'
		),
		'album_delete' => array(
			'method' => 'GET',
			'route' => '[i:id_album]/delete',
			'ctrl' => 'album',
			'action' => 'delete'
		),
		'picture' => array(
			'route' => '/picture/[i:id_picture]',
			'ctrl' => 'picture',
			'action' => 'index'
		),
		'picture_edit' => array(
			'route' => '/picture/[i:id_picture]/edit',
			'ctrl' => 'picture',
			'action' => 'edit'
		),
		'picture_random' => array(
			'route' => '/picture/random',
			'ctrl' => 'picture',
			'action' => 'random',
		),
		'picture_upload' => array(
			'method' => 'GET|POST',
			'route' => '/upload/[i:id_album]?',
			'ctrl' => 'picture',
			'action' => 'upload',
		),
	);

	protected function initPaths()
	{
		parent::initPaths();

		// fileupload dir and url
		$this->cfg('dir_gallery_upload', BOARDDIR . $this->cfg('path'));
		$this->cfg('url_gallery_upload', BOARDURL . $this->cfg('path'));
	}

	public function onBefore()
	{
		return '<div id="gallery">';
	}

	public function onAfter()
	{
		return '</div>';
	}

	/*
	 * Creates the arrayelements of Raidmanager menu.
	 */
	public function addMenuButtons(&$menu_buttons)
	{
		// Load the list of accessible albums
		$album_list = $this->getModel('Album')->getAlbumList();

		$gallery_menu_buttons = array();

		if ($album_list)
		{
			$gallery_album_buttons = array();

			foreach ($album_list as $album)
			{
				$gallery_album_buttons['gallery_' . FileIO::cleanFilename($album->title)] = array(
					'title' => $album->title,
					'href' => Url::factory('gallery_album_album', array('id_album' => $album->id_album))->getUrl(),
					'show' => true,
				);
			}

			$gallery_menu_buttons['gallery_album_list'] = array(
				'title' => $this->txt('album'),
				'href' => Url::factory('gallery_album_index')->getUrl(),
				'show' => true,
				'sub_buttons' => $gallery_album_buttons,
			);
		}

		$gallery_menu_buttons['gallery_upload'] = array(
			'title' => $this->txt('upload'),
			'href' => Url::factory('gallery_picture_upload')->getUrl(),
			'show' => $this->checkAccess('allow_upload')
		);
		$gallery_menu_buttons['gallery_new'] = array(
			'title' => $this->txt('album_new'),
			'href' => Url::factory('gallery_album_new')->getUrl(),
			'show' => $this->checkAccess('gallery_manage_album')
		);
		$gallery_menu_buttons['gallery_config'] = array(
			'title' => $this->txt('web_config'),
			'href' => Url::factory('admin_app_config', array('app_name' => 'gallery'))->getUrl(),
			'show' => $this->checkAccess('gallery_manage_album')
		);

		$menu_buttons['gallery'] = array(
			'title' => $this->txt('headline'),
			'href' => Url::factory('gallery_album_index')->getUrl(),
			'show' => !empty($album_list) || $this->checkAccess('gallery_manage_album'),
			'sub_buttons' => $gallery_menu_buttons
		);
	}

	/**
	 * Writes membername to the picture and gallery tables of the member to delete
	 * @param int $id_member
	 */
	public function onMemberDelete($id_member)
	{
		// Get the member name by member id
		$model = App::getInstance('forum')->getModel('members');
		$model->find($id_member, array('member_name', 'real_name'));
		$member_name = $member->real_name ? $member->real_name : $member->member_name;

		// Create Gallery app
		$app = App::create('Gallery');

		// Update the albums of this member
		$model = $app->getModel('Album');
		$model->setField('member_name');
		$model->setFilter('id_member={int:id_member}');
		$model->setParameter(array(
			'membername' => $member_name,
			'id_member' => $id_member
		));
		$model->update();

		// And then the pictures
		$model = $app->getModel('Picture');
		$model->setField('member_name');
		$model->setFilter('id_member={int:id_member}');
		$model->setParameter(array(
			'membername' => $member_name,
			'id_member' => $id_member
		));
		$model->update();
	}
}
?>