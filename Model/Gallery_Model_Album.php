<?php
namespace Web\Apps\Gallery\Model;

use Web\Framework\Lib\Url;
use Web\Framework\Lib\Model;
use Web\Framework\Lib\Image;
use Web\Framework\Lib\User;

class Gallery_Model_Album extends Model
{
	public $tbl = 'app_gallery_albums';
	public $alias = 'album';
	public $afterCreate = array('createPath');


	public function getAlbums()
	{
		// load all albums data
		$this->Read('all');

		foreach($this->data as $album)
		{
			// check rights for access, upload and editing
			$this->checkRights($album);

			// album without accessright need to be removed
			if (!$album->allow_access)
			{
				unset($this->data[$album->id_album]);
				continue;
			}

			// create album url
			$album->url = Url::factory('gallery_album_album')
							->addParameter('id_album', $album->id_album)
							->getUrl();

			// get random album image
			$album->image = $this->App->Model('Picture')->getRndAlbumPicture($album->id_album);
		}

		return $this->data;
	}

	public function getAlbumInfos($id_album)
	{
		// get album data
		$this->Find($id_album);

		// chekc accessrights
		$this->checkRights($this->data);

		if($this->data->allow_access)
		{
			// user is allowed to see the gallery
			$this->data->url = Url::factory('gallery_album_album')
								->addParameter('id_album', $id_album)
								->getUrl();

			if (!$this->data->legal)
				$this->data->legal = $this->Text('gallery_legal');
		}
		else
			$this->data = false;

		return $this->data;
	}

	/**
	 * get album ids the current user can access
	 * @return Ambigous <boolean, multitype:NULL >
	 */
	public function getAlbumIDs()
	{
		$out = array();

		$albums = $this->Read('all');

		foreach($albums as $album)
		{
			// init album access checks
			$this->checkRights($album);

			// nex album if missing accessrights
			if (!$album->allow_access)
				continue;

			$out[] = $album->id_album;
		}

		// no gallery ids $out has to be false
		if (count($out) == 0)
			$out = false;

		return $out;
	}

	public function getAlbum($id_album)
	{
		$this->getAlbumInfos($id_album);

		if($this->hasData())
			$this->data->pictures = $this->App->Model('Picture')->getAlbumPictures($id_album);
		else
			$this->data = false;

		return $this->data;
	}

	private function checkRights(&$album)
	{
		// access managed by set accessgroups?
		$album->allow_access = $this->checkAccessgroups($album);

		// check upload rights
		$album->allow_upload = $this->checkUploadgroups($album);

		// check edit rights
		$album->allow_edit = (User::getId() == $album->id_member || $this->checkAccess(array('admin', 'gallery_admin'))) ? true : false;
	}

	private function checkAccessgroups($album)
	{

		// albums open for anonymous users will be shown to everyone
		if (!isset($album->accessgroups) || (isset($album->accessgroups) && isset($album->accessgroups->{-1})))
			return true;

		// admins get always access
		if ($this->checkAccess(array('admin', 'gallery_admin')))
			return true;

		// album owner always has access to his own album
		if (User::getId() == $album->id_member)
			return true;

		// check usergroups
		if (isset($album->accessgroups))
		{
			$groups = User::getInfo('groups');

			foreach($groups as $id_group)
			{
				if(isset($album->accessgroups->{$id_group}))
					return true;
			}
		}

		// anyone else will get no access
		return false;
	}

	private function checkUploadgroups(&$album)
	{
		// admins get always access
		if ($this->checkAccess(array('admin', 'gallery_admin')))
			return true;

		// album owner always has access to his own album
		if (User::getId() == $album->id_member)
			return true;

		// check usergroups
		if (isset($album->uploadgroups))
		{
			$groups = User::getInfo('groups');

			foreach($groups as $id_group)
			{
				if(isset($album->uploadgroups->{$id_group}))
					return true;
			}
		}

		// anyone else will get no access
		return false;
	}

	public function convertGalleries()
	{
		// get galleries from db
		$galleries = $this->Read();

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
			$path_gallery = $this->Cfg('dir_album') . $dir_name;
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
			$images = $this->App->Model('Picture')->setFilter('id_album={int:id_album}')->setParameter('id_album', $album->id_album)->Read();

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
					Image::Resize($path_image, $this->Cfg('gallery_thumb_width'), $path_image_thumb, null, 90);
					echo '<p>thumb created.<p>';
				}

				// medium size
				$path_image_medium = $path_medium . '/' . $filename;

				if (!file_exists($path_image_medium))
				{
					Image::Resize($path_image, $this->Cfg('gallery_medium_width'), $path_image_medium, null, 90);
					echo '<p>medium created.<p>';
				}

				$this->App->Model('Picture')->setData($image)->Save();
			}

			$this->setData($album)->cleanMode('off')->Save();
		}
	}

	public function getEdit($id_album=null)
	{
		// for info edits
		if(isset($id_album))
		{
			$this->Find($id_album);
			$this->data->mode = 'edit';
		}
		else
		{
			// create empty data container
			$this->data = new \stdClass();

			// some default values and dateconversions for the datepicker
			$this->data->title = '';
			$this->data->description = '';

			$this->data->accessgroups = '';
			$this->data->id_member = User::getId();
			$this->data->date_created = time();
			$this->data->tags = '';
			$this->data->image = '';
			$this->data->uploadgroups = '';
			$this->data->scoring = 1;
			$this->data->img_per_user = 0;
			$this->data->category = '';
			$this->data->special = '';
			$this->data->notes = '';
			$this->data->max_scores = 0;
			$this->data->anonymous = 0;
			$this->data->legalinfo = $this->Cfg('gallery_default_legal');
			$this->data->dir_name = '';
			$this->data->mode = 'new';
		}

		return $this->data;
	}

	public function saveAlbum()
	{
		// no empty accessgroups field
		if (!isset($this->data->uploadgroups))
			$this->data->uploadgroups = new \stdClass();

		// no empty accessgroups field
		if (!isset($this->data->accessgroups))
			$this->data->accessgroups = new \stdClass();

		$this->Save();
	}




}
?>