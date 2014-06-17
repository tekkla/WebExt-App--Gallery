<?php
namespace Web\Apps\Gallery\Model;

use Web\Framework\Lib\Url;
use Web\Framework\Lib\Model;
use Web\Framework\Lib\User;
use Web\Framework\Lib\Data;
use Web\Framework\Lib\FileIO;

/**
 * Album model
 * @author Michael "Tekkla" Zorn <tekkla@tekkla.de>
 * @package App Gallery
 * @subpackage Model/Album
 * @license BSD
 * @copyright 2014 by author
 */
final class AlbumModel extends Model
{
	protected $tbl = 'app_gallery_albums';
	protected $alias = 'album';
	protected $afterCreate = array('createPath');
	protected $serialized = array('accessgroups', 'uploadgroups', 'mime_types');
	public $validate = array(
		'title' => array('empty')
	);

	/**
	 * Returns all albums with data accessible to to the current user
	 */
	public function getAlbums()
	{
		// load all albums data
		return $this->read('*', array('checkGroupsAccess', 'processAlbum'));
	}

	/**
	 * Callback function to enhance albumdata on read
	 * @param $album The album to process
	 */
	public function processAlbum(&$album)
	{
		// Get random album image
		$album->image = $this->getModel('Picture')->getRndAlbumPicture($album->id_album);

		// No album image means we have an empty album
		if (!$album->image)
		{
			$album->image = new Data();
			$album->image->src = $this->cfg('url_images') . '/gallery_empty.jpg';
		}

		// Check upload rights
		$this->checkGroupsUpload($album);

		// Check edit rights
		$album->allow_edit = User::getId() == $album->id_member || $this->checkAccess('gallery_manage_album');

		// Create album url
		$album->url = Url::factory('gallery_album_album', array('id_album' => $album->id_album))->getUrl();

		return $album;
	}

	/**
	 * Returns the data of one specific album. Checks also the accessrights of
	 * the current user.
	 */
	public function getAlbumInfos($id_album)
	{
		$this->find($id_album, null, array('checkGroupsAccess', 'processAlbum'));

		if (!$this->hasData())
			return false;

		if (!$this->data->legalinfo)
			$this->data->legalinfo = $this->txt('legal');

		// Check for differences in set MIME-types and app wide MIME-types
		if (!$this->cfg('upload_mime_types') && $this->data->mime_types)
		{
			// Remove set MIME-types from album
			$this->updateMimeTypes($id_album);
		}

		// Compare config MIME-types and album MIME-types
		if ($this->cfg('upload_mime_types') && $this->data->mime_types)
		{
			$to_unset = array_diff($this->data->mime_types->getProperties(), $this->cfg('upload_mime_types')->getProperties());

			foreach ($to_unset as $unset)
				unset($this->data->mime_types->$unset);

			$this->updateMimeTypes($id_album, $this->data->mime_types);
		}

		return $this->data;
	}

	/**
	 * Updates MIME-types field of a specific album by the types given.
	 * @param int $id_album Id of album
	 * @param null|Data $mime_types Optional MIME-types to store.
	 */
	private function updateMimeTypes($id_album, $mime_types=null)
	{
		$model = $this->getModel();

		$model->data = new Data();
		$model->data->id_album = $id_album;
		$model->data->mime_types = isset($mime_types) ? $mime_types : 'NULL';

		$model->save(false);
 	}

	/**
	 * Get album ids the current user can access
	 */
	public function getAlbumIDs()
	{
		$this->read(array(
			'type' => '*',
			'field' => array(
				'id_album',
				'accessgroups'
			)
		), 'checkGroupsAccess');

		return $this->data->keys();
	}

	/**
	 * Returns data of a specific album
	 * @param int $id_album Id of album
	 * @return \Web\Framework\Lib\Data
	 */
	public function getAlbum($id_album)
	{
		$this->getAlbumInfos($id_album);

		if($this->hasData())
			$this->data->pictures = $this->getModel('Picture')->getAlbumPictures($id_album);
		else
			$this->data = false;

		$this->processAlbum($this->data);

		return $this->data;
	}

	/**
	 * Callback method check useracces on an album.
	 * Returns the $album on positive access or FALSE when accescheck failed.
	 * @param \Web\Framework\Lib\Data $album
	 * @return \Web\Framework\Lib\Data|boolean
	 */
	public function checkGroupsAccess($album)
	{
		// Admins get always access
		if ($this->checkAccess('gallery_manage_album'))
			return $album;

		// Albums open for anonymous users will be shown to everyone
		if (!isset($album->accessgroups) || (isset($album->accessgroups) && isset($album->accessgroups->{-1})))
			return $album;

		// Album owner always has access to his own album
		if (User::getId() == $album->id_member)
			return $album;

		// Check set accessgroups
		if (isset($album->accessgroups))
		{
			$groups = User::getInfo('groups');

			foreach($groups as $id_group)
			{
				if(isset($album->accessgroups->{$id_group}))
					return $album;
			}
		}

		// All checks negative? Return false.
		return false;
	}

	/**
	 * Checks the album on set mime types to determine if upload is active or not.
	 * @param int $id_album Id of album
	 * @return boolean
	 */
	public function checkUploadActive($id_album)
	{
		return isset($this->getModel()->find($id_album)->mime_types);
	}

	/**
	 * Callback method to check upload rights of user.
	 * Takes care of set mime types to deactivate upload when nothing is set.
	 * @param \Web\Framework\Lib\Data $album Album to process
	 * @return \Web\Framework\Lib\Data
	 */
	public function checkGroupsUpload($album)
	{
		// By default upload is not allowed
		$album->allow_upload = false;

		// admins get always access
		if ($this->checkAccess('gallery_manage_album') || User::getId() == $album->id_member)
		{
			$album->allow_upload = true;
		}
		// check usergroups
		elseif (isset($album->uploadgroups))
		{
			$groups = User::getInfo('groups');

			foreach($groups as $id_group)
			{
				if(isset($album->uploadgroups->{$id_group}))
				{
					$album->allow_upload = true;
					break;
				}
			}
		}
		// Default: deny uploads
		else
		{
			$album->allow_upload = false;
		}

		// Regardless what was set for upload before.
		// No MIME-types set means no upload at all!
		if (!isset($album->mime_types))
			$album->allow_upload = false;

		return $album;
	}

	/**
	 * Returns data for editing a specific album or a new one.
	 * @param int $id_album Optional album id. Set id means edit, none set means new album.
	 * @return \Web\Framework\Lib\Data
	 */
	public function getEdit($id_album=null)
	{

		// If user lacks of proper access rights, return false
		if (!$this->checkAccess('gallery_manage_album'))
		{
			$this->data = false;
			return $this->data;
		}

		// for info edits
		if(isset($id_album))
		{
			// Load album data
			$this->find($id_album, null, 'processAlbum');
			$this->data->mode = 'edit';
		}
		else
		{
			// create empty data container
			$data = new Data();

			// some default values and dateconversions for the datepicker
			$data->title = '';
			$data->description = '';

			$data->accessgroups = '';
			$data->id_member = User::getId();
			$data->date_created = time();
			$data->tags = '';
			$data->image = '';
			$data->uploadgroups = '';
			$data->scoring = $this->cfg('scoring');
			$data->img_per_user = 0;
			$data->category = '';
			$data->special = '';
			$data->notes = '';
			$data->max_scores = 0;
			$data->anonymous = 0;
			$data->legalinfo = $this->cfg('default_legal');
			$data->dir_name = '';
			$data->mode = 'new';

			$this->data = $data;
		}

		return $this->data;
	}

	/**
	 * Saves album data to db
	 * @param \Web\Framework\Lib\Data $data
	 */
	public function saveAlbum($data)
	{
		$stamp = time();

		$this->data = $data;

		// First validate user input
		$this->validate();

		// Any errors?
		if ($this->hasErrors())
			return;

		// No set album id means we have a new album to create
		if (!isset($this->data->id_album))
		{
			// Check for existing album title. If it exits we add an error and stop here
			if ($this->checkAlbumTitleExists($this->data->title))
			{
				$this->addError('@', $this->txt('album_error_title_already_exists'));
				return;
			}

			// Create the album directory by using uniqid(). An album directory has to be really unique, so we
			// give this part 100 chances to get a correct name.
			$dir_exists = true;
			$max_tries = 100;
			$tries = 0;

			while ($dir_exists == true && $tries < $max_tries)
			{
				// Create dir name
				$this->data->dir_name = uniqid('album.');

				// Dir path
				$album_path =  $this->cfg('dir_gallery_upload') . '/' . $this->data->dir_name;

				// Does this dir exists?
				$dir_exists = FileIO::exists($album_path);
				$tries++;
			}

			// Still an existing directory? At this point an error is added to the model and we stop here.
			if ($dir_exists)
			{
				$this->addError('@', $this->txt('album_error_dir_exists'));
				return;
			}

			// Create album directory
			$dir_created = FileIO::createDir($album_path);

			if (!$dir_created)
			{
				$this->addError('@', $this->txt('album_error_dir_creation_failed'));
				return;
			}
			else
			{
				// 	And create thumbs dir in the album dir
				$dir_created = FileIO::createDir($album_path . '/thumbs');

				if (!$dir_created)
				{
					$this->addError('@', $this->txt('album_error_thumb_dir_creation_failed'));
					return;
				}
			}

			// Add some basic infos about the creation date and the album owner
			$this->data->date_created = $stamp;
			$this->data->id_member = User::getId();
		}

		// Set update date and the member id of user who did the update
		$this->data->date_updated = $stamp;
		$this->data->id_updater = User::getId();

		if (!isset($this->data->mime_types))
			$this->data->mime_types = NULL;

		// Any errors?
		if ($this->hasErrors())
			return;

		// Save data without any further validation
		$this->save(false);
	}

	/**
	 * Returns the title of one specific album.
	 * @param int $id_album Id of album
	 * @return string
	 */
	public function getAlbumTitle($id_album)
	{
		return $this->read(array(
			'type' => 'val',
			'field' => 'title',
			'filter' => 'id_album={int:id_album}',
			'param' => array(
				'id_album' => $id_album
			),
		));
	}

	/**
	 * Returns a list if albums accessible to the current user.
	 * @return \Web\Framework\Lib\Data
	 */
	public function getAlbumList()
	{
		$this->setField(array(
			'id_album',
			'title',
			'id_member',
			'accessgroups',
			'uploadgroups',
			'mime_types',
		));

		return $this->read('*', array('checkGroupsAccess', 'checkGroupsUpload'));
	}

	/**
	 * Returns the systempath of the requested album
	 * @param int $id_album
	 * @return string
	 */
	public function getAlbumPath($id_album)
	{
		$this->setField('dir_name');
		$this->setFilter('id_album = {int:id_album}', array('id_album' => $id_album));
		return $this->cfg('dir_gallery_upload') . '/' . $this->read('val');
	}

	/**
	 * Checks for an existing album title
	 * @param string $title
	 * @return boolean
	 */
	private function checkAlbumTitleExists($title)
	{
		return $this->getModel()->count('title={string:title}', array('title' => $title)) > 0 ? true : false;
	}

	/**
	 * Deletes a specific album and it's pictures
	 * @param int $id_album Id of album to delete
	 * @return boolean
	 */
	public function deleteAlbum($id_album)
	{
		$this->getAlbumInfos($id_album);

		if (!$this->data->allow_edit)
		{
			$this->addError('@', false);
			return false;
		}
		else
		{
			// Delete all pictures of this album
			$this->getModel('Picture')->deleteAlbumPictures($id_album);

			// Delete album folder
			$path = $this->cfg('dir_uploads') . '/' . $this->data->dir_name;
			FileIO::deleteDir($path);

			// Delete the album itself
			$this->delete($id_album);
		}
	}
}
?>
