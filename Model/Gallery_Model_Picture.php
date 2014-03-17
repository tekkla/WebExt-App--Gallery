<?php
namespace Web\Apps\Gallery\Model;

use	Web\Framework\Lib\Model;
use	Web\Framework\Lib\Url;
use Web\Framework\Lib\User;
use Web\Framework\Lib\FileIO;

use Web\Framework\Html\Controls\UiButton;




class Gallery_Model_Picture extends Model
{
	public $tbl = 'app_gallery_pictures';
	public $alias = 'pic';

	public function getRndAlbumPicture($id_album)
	{
		// general filter
		$this->setFilter('pic.id_album={int:id_album}', array(
			'id_album' => $id_album
		));

		// get random row
		$this->addField('FLOOR(RAND() * COUNT(*)) AS rand_row');
		$rand_row = $this->Read('onevalue');

		$this->setField(array(
			'pic.id_picture',
			'pic.title',
			'pic.description',
			'pic.id_member',
			'pic.picture',
			'CONCAT("' . $this->Cfg('url_gallery') . '", "/", album.dir_name, "/thumbs/", pic.picture) AS src',
		));

		$this->setJoin('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album');

		// only one random pic
		$this->setLimit($rand_row, 1);

		$this->Read();

		// get picture
		$this->checkData();

		// create picture src url
		//$picture->src = $this->Cfg('url_gallery') . '/thumbs/' . $picture->thumb;

		return $this->data;
	}

	public function getAlbumPictures($id_album)
	{
		$this->setField(array(
			'pic.id_picture',
			'pic.title',
			'pic.description',
			'pic.id_member',
			'pic.picture',
			'CONCAT("' . $this->Cfg('url_gallery') . '", "/", album.dir_name, "/thumbs/", pic.picture) AS src',
		));
		$this->setJoin('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album');
		$this->setFilter('pic.id_album={int:id_album}');
		$this->setParameter('id_album', $id_album);
		$this->setOrder('pic.date_upload DESC');

		$this->Read('all');

		// link to picture detail page
		$url = Url::factory('gallery_picture');

		foreach($this->data as $picture)
		{
			if(!$picture->title)
				$picture->title = $this->Text('gallery_picture_without_title');

			// complete url to d
			$picture->url = $url->addParameter('id_picture', $picture->id_picture)->getUrl();
		}

		return $this->data;
	}

	public function getRndPicture()
	{
		// get all gallery ids accessible for this user
		$albums = $this->App->Model('Album')->getAlbumIDs();


		// if $galleries is false, the gallery model returned no data.
		// no data means we can stop our work here.
		if($albums === false)
			return false;

		// only pictures from galleries the user can access
		$this->setFilter('pic.id_album IN ({array_int:albums})');
		$this->addParameter('albums', $albums);

		$this->addField('FLOOR(RAND() * COUNT(*)) AS rand_row');
		$rand_row = $this->Read('onevalue');

		// we wanne use our model for furher actions, but without the rand stuff
		$this->setField(array(
			'pic.id_picture',
			'pic.title',
			'pic.description',
			'pic.id_member',
			'pic.picture',
			'CONCAT("' . $this->Cfg('url_gallery') . '", "/", album.dir_name, "/", pic.picture) AS src',
		));

		$this->setJoin('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album');

		// only one pic
		$this->setLimit($rand_row, 1);

		$data = $this->Read();

		// link to picture detail page
		$data->page = Url::factory('gallery_picture')
							->addParameter('id_picture', $data->id_picture)
							->getUrl();
		return $data;
	}

	public function getPicture($id_picture = null)
	{
		// get picture
		$this->setField(array(
			'pic.*',
			'if(mem.real_name, mem.real_name, mem.member_name) AS owner',
			'album.dir_name',
			'CONCAT("' . $this->Cfg('url_gallery') . '", "/", album.dir_name, "/", pic.picture) AS src',
			'CONCAT("' . $this->Cfg('url_gallery') . '", "/", album.dir_name, "/", pic.picture) AS src_medium',
			'CONCAT("' . $this->Cfg('url_gallery') . '", "/", album.dir_name, "/", pic.picture) AS src_thumb',
		));
		$this->setJoin('members', 'mem', 'LEFT', 'mem.id_member=pic.id_member');
		$this->addJoin('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album');
		$this->setFilter('id_picture = {int:id_picture}', array(
			'id_picture' => $id_picture
		));

		$this->Read();

		// check picturedata for missing informations
		#$this->checkData($pictur);

		// get gallerydata
		$this->data->gallery = $this->App->Model('Album')->getAlbumInfos($this->data->id_album);

		// create membername
		if(!$this->data->gallery->anonymous && $this->data->owner)
		{
			$button = UiButton::factory('full', 'link');
			$button->Url->setAction('profile');
			$button->Url->addParameter('u', $this->data->id_member);

			$this->data->owner= $button->setText($this->data->owner)->Create();
		}

		$this->data->filesize = FileIO::convFilesize($this->data->filesize);

		return $this->data;
	}

	/**
	 * Check picture content for missing informations
	 * The checks are: path, url, picturesize, filesize
	 *
	 * @param Data $picture
	 */
	private function checkData()
	{

		return true;

		$do_update = false;



		// possible change of picturename
		$img_org = $this->data->image;

		$img_alt = str_replace(' ', '_', $img_org);
		$img_alt = preg_replace('/[^a-z0-9_\.\-[:space:]]/i', '_', $img_alt);

		// name altered?
		if($img_org != $img_alt)
		{
			#$img_alt = $this->data->id_member . '_' . $this->data->date_uploaded . '_' . $img_alt;

			// rename file and thumb
			$path = $this->Cfg('dir_gallery') . $this->data->id_album . '/pictures/' . $this->data->id_member . '/';
			rename($path . $img_org, $path . $img_alt);
			rename($path . 'thumb_' . $img_org, $path . 'thumb_' . $img_alt);

			// save new filename
			$this->data->picture = $img_alt;

			// rewirte path and url data
			$this->data->path = $this->Cfg('dir_gallery') . '/' . $this->data->picture;
			$this->data->url_picture = htmlspecialchars($this->Cfg('url_gallery') . '/' . $this->data->picture);
			$this->data->url_thumb = htmlspecialchars($this->Cfg('url_gallery') . '/thumb/' . $this->data->picture);
			$this->data->url_news = htmlspecialchars($this->Cfg('url_gallery') . '/news/' . $this->data->picture);

			if( ! file_exists($this->data->path))
				return 404;

			$do_update = true;
		}

		if( ! $this->data->member_name)
		{
			$this->data->member_name = 'nobody';
			$do_update = true;
		}

		if( ! $this->data->real_name)
		{
			$this->data->real_name = 'nobody';
			$do_update = true;
		}

		if( ! $this->data->path)
		{
			$this->data->path = $this->Cfg('dir_gallery') . $this->data->id_album . '/pictures/' . $this->data->id_member . '/' . $this->data->picture;

			if( ! file_exists($this->data->path))
				return 404;

			$do_update = true;
		}

		if( ! $this->data->url_picture)
		{
			$this->data->url_picture = htmlspecialchars($this->Cfg('url_gallery') . $this->data->id_album . '/pictures/' . $this->data->id_member . '/' . $this->data->picture);
			$do_update = true;
		}

		if( ! $this->data->url_thumb)
		{
			$this->data->url_thumb = htmlspecialchars($this->Cfg('url_gallery') . $this->data->id_album . '/pictures/' . $this->data->id_member . '/thumb_' . $this->data->picture);
			$do_update = true;
		}

		if( ! $this->data->width ||  ! $this->data->height)
		{
			if( ! file_exists($this->data->path))
				return 404;

			$imginfo = getimagesize($this->data->path);

			$this->data->width = $imginfo[0];
			$this->data->height = $imginfo[1];

			$do_update = true;
		}

		if( ! $this->data->filesize)
		{
			if( ! file_exists($this->data->path))
				return 404;

			$this->data->filesize = filesize($this->data->path);
			$do_update = true;
		}

		if($do_update == true)
		{
			$mem = $this->data->member_name;
			$real = $this->data->real_name;

			unset($this->data->member_name);
			unset($this->data->real_name);

			$this->setData($this->data)->Save();

			$this->data->member_name = $mem;
			$this->data->real_name = $real;

			echo __METHOD__ . '<br>';
			echo $this->debug($this->data) . '<hr>';
		}

		return $this->data;
	}

	function deletePicture($id_picture)
	{
		// load current picture data
		$this->Find($id_picture);

		// if fitting accessrights or owner of picture
		if(allowedTo('gallery_picture_delete') || $this->data->id_member == User::getId())
		{
			$this->Delete($id_picture);
			return true;
		}
		else
			return false;
	}

	function deleteAllPicturesOfUser($id_user=null)
	{
		// id_user as argument and allowed to delete gallery pictures?
		if(isset($id_user) && allowedTo('gallery_picture_delete'))
		{
			$this->setFilter('id_member = {int:id_member}');
			$this->setParameter('id_member', $id_user);
			return true;
		}

		// no argument, delete the pictures of current user
		if(!isset($id_user))
		{
			$this->setFilter('id_member = {int:id_member}');
			$this->setParameter('id_member', User::getId());
			return true;
		}

		return false;
	}

	function deleteAlbumPictures($id_album)
	{

		if (!allowedTo('gallery_picture_delete'))
			return false;

		$this->setFilter('id_album = {int:id_album}');
		$this->Read('all');

		$to_delete = array();

		foreach ($this->data  as $picture)
		{
			$to_delete[] = $picture->id_picture;

			if (file_exists($picture->path))
				unlink($picture->path);
		}

		$this->setFilter('id_picture IN {array_int:to_delete}');
		$this->setParameter('to_delete', $to_delete);
		$this->Delete();



		// id_user as argument and allowed to delete gallery pictures?
		if(isset($id_user) && allowedTo('gallery_picture_delete'))
		{
			$this->setFilter('id_member = {int:id_member}');
		#$this->setParameter('id_member', $id_user);
			return true;
		}

		// no argument, delete the pictures of current user
		if(!isset($id_user))
		{
			$this->setFilter('id_member = {int:id_member}');
			$this->setParameter('id_member', User::getId());
			return true;
		}

		return false;
	}
}

?>