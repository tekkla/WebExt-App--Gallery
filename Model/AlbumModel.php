<?php
namespace Web\Apps\Gallery\Model;

use Web\Framework\Lib\Url;
use Web\Framework\Lib\Model;
use Web\Framework\Lib\Image;
use Web\Framework\Lib\User;
use Web\Framework\Lib\Data;
use Web\Framework\Lib\FileIO;

class AlbumModel extends Model
{
	public $tbl = 'app_gallery_albums';
	public $alias = 'album';
	public $afterCreate = array('createPath');
	public $serialized = array('accessgroups', 'uploadgroups');
	public $validate = array(
		'title' => 'empty'
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
		$album->image = $this->app->getModel('Picture')->getRndAlbumPicture($album->id_album);

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

		return $this->data;
	}

	/**
	 * Get album ids the current user can access
	 */
	public function getAlbumIDs()
	{
		$this->setField(array(
			'id_album',
			'accessgroups'
		));

		$this->read('*', 'checkGroupsAccess');

		return array_keys(get_object_vars($this->data));
	}

	public function getAlbum($id_album)
	{
		$this->getAlbumInfos($id_album);

		if($this->hasData())
			$this->data->pictures = $this->app->getModel('Picture')->getAlbumPictures($id_album);
		else
			$this->data = false;

		$this->processAlbum($this->data);

		return $this->data;
	}

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
	}

	public function convertGalleries()
	{
		// get galleries from db
		$galleries = $this->read();

		// update each of them
		foreach($galleries as $album)
		{
			// create a readable dir name for gallery
			#if ($album->dir_name)
			#	continue;

			echo '<hr>converting gallery: ' . $album->title .'<br>';

			// get current title for usage as dirname of gallery
			$title = $album->title;

			$dir_name = preg_replace('/\%/',' percentage',$title);
			$dir_name = preg_replace('/\@/',' at ',$dir_name);
			$dir_name = preg_replace('/\&/',' and ',$dir_name);
			$dir_name = preg_replace('/\s[\s]+/','-',$dir_name);    // Strip off multiple spaces
			$dir_name = preg_replace('/[\s\W]+/','-',$dir_name);    // Strip off spaces and non-alpha-numeric
			$dir_name = preg_replace('/^[\-]+/','',$dir_name); // Strip off the starting hyphens
			$dir_name = preg_replace('/[\-]+$/','',$dir_name); // // Strip off the ending hyphens
			#$dir_name = strtolower($dir_name);

			// dir name complete, store it as value
			$album->dir_name = $dir_name;

			// create absolute path of gallery dir
			$path_gallery = $this->cfg('dir_album') . $dir_name;
			$path_thumbs = $path_gallery . '/thumbs';
			$path_medium = $path_gallery . '/medium';

			// if path does not exist, create it
			if (!file_exists($path_gallery))
			{
				echo 'creating path ... ' . $path_gallery . '<br>';
				mkdir($path_gallery);

				// per default we have a thumbs dir in each gallery.
				mkdir($path_thumbs);

				mkdir($path_medium);
			}

			// create thumb dir if not existing.
			if (!file_exists($path_thumbs))
			{
				echo 'creating thumbs path ... ' . $path_thumbs . '<br>';
				mkdir($path_thumbs);
			}

			// create thumb dir if not existing.
			if (!file_exists($path_medium))
			{
				echo 'creating medium path ... ' . $path_medium . '<br>';
				mkdir($path_medium);
			}

			// get the images of this gallery
			$images = $this->app->getModel('Picture')->setFilter('id_album={int:id_album}')->setParameter('id_album', $album->id_album)->read();

			// conversion for each image
			foreach ($images as $image)
			{
				// this is for my own gallery and name conversion
				setlocale(LC_ALL, 'de_DE.utf8');

				// clean up non acscii symbols from the image title
				$filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $image->title);

				$filename = preg_replace('/\%/',' percentage',$filename);
				$filename = preg_replace('/\@/',' at ',$filename);
				$filename = preg_replace('/\&/',' and ',$filename);
				$filename = preg_replace('/\s[\s]+/','-',$filename);    // Strip off multiple spaces
				$filename = preg_replace('/[\s\W]+/','-',$filename);    // Strip off spaces and non-alpha-numeric
				$filename = preg_replace('/^[\-]+/','',$filename); // Strip off the starting hyphens
				$filename = preg_replace('/[\-]+$/','',$filename);

				// create new unique filename with md5 hash out of member id and upload timestamp
				$filename = $filename .'-' . md5($image->id_member . $image->date_upload) . '.' . pathinfo($image->path, PATHINFO_EXTENSION);

				$path_image = $path_gallery . '/' . $filename;

				// check file existance
				if (!file_exists($path_image))
				{
					// copy image to new place under a new name
					if (copy($image->path, $path_image))
					{
						unlink($image->path);
						$image->image = $filename;
						$image->date_update = time();

						if (!$image->type)
						{
							$info  = getimagesize($path_image);
							$image->type  = $info['mime'];
						}
					}
				}

				// thumb
				$path_image_thumb = $path_thumbs . '/' . $filename;

				if (!file_exists($path_image_thumb))
				{
					Image::Resize($path_image, $this->cfg('gallery_thumb_width'), $path_image_thumb, null, 90);
					echo '<p>thumb created.<p>';
				}

				// medium size
				$path_image_medium = $path_medium . '/' . $filename;

				if (!file_exists($path_image_medium))
				{
					Image::Resize($path_image, $this->cfg('gallery_medium_width'), $path_image_medium, null, 90);
					echo '<p>medium created.<p>';
				}

				$this->app->getModel('Picture')->setData($image)->save();
			}

			$this->setData($album)->cleanMode('off')->save();
		}
	}

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

		// Any errors?
		if ($this->hasErrors())
			return;

		$this->save(false);
	}

	public function getAlbumTitle($id_album)
	{
		$this->setField('title');
		$this->setFilter('id_album={int:id_album}', array('id_album' => $id_album));
		return $this->read('val');
	}

	public function getAlbumList()
	{
		$this->setField(array(
			'id_album',
			'title',
			'id_member',
			'accessgroups',
			'uploadgroups'
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

	private function checkAlbumTitleExists($title)
	{
		$model = $this->app->getModel('Album');
		$model->setFilter('title={string:title}', array('title' => $title));

		$test = $model->read();

		$num = $model->count();

		return  $num > 0 ? true : false;
	}

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
			$this->app->getModel('Picture')->deleteAlbumPictures($id_album);

			// Delete album folder
			$path = $this->cfg('dir_uploads') . '/' . $this->data->dir_name;
			FileIO::deleteDir($path);

			// Delete the album itself
			$this->delete($id_album);
		}
	}
}
?>