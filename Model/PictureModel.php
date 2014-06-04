<?php
namespace Web\Apps\Gallery\Model;

use	Web\Framework\Lib\Model;
use	Web\Framework\Lib\Url;
use Web\Framework\Lib\User;
use Web\Framework\Lib\FileIO;
use Web\Framework\Lib\SimpleImage;


class PictureModel extends Model
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
		$this->setField('FLOOR(RAND() * COUNT(*)) AS rand_row');
		$rand_row = $this->read('val');

		$this->setField(array(
			'pic.id_picture',
			'pic.title',
			'pic.description',
			'pic.id_member',
			'pic.picture',
			'CONCAT("' . $this->cfg('url_gallery_upload') . '", "/", album.dir_name, "/thumbs/", pic.picture) AS src',
		));

		$this->setJoin('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album');

		// only one random pic
		$this->setLimit($rand_row, 1);

		return $this->read();
	}

	public function getAlbumPictures($id_album)
	{
		$this->setField(array(
			'pic.id_picture',
			'pic.title',
			'pic.description',
			'pic.id_member',
			'pic.picture',
			'CONCAT("' . $this->cfg('url_gallery_upload') . '", "/", album.dir_name, "/thumbs/", pic.picture) AS src',
		));
		$this->setJoin('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album');
		$this->setFilter('pic.id_album={int:id_album}');
		$this->setParameter('id_album', $id_album);
		$this->setOrder('pic.date_upload DESC');

		return $this->read('*', 'processAlbumPicture');
	}

	public function processAlbumPicture($picture)
	{
		// link to picture detail page
		$picture->url = Url::factory('gallery_picture', array('id_picture' => $picture->id_picture))->getUrl();

		if(!isset($picture->title))
			$picture->title = $this->txt('picture_without_title');

		return $picture;
	}

	public function getRndPicture()
	{
		// get all gallery ids accessible for this user
		$albums = $this->app->getModel('Album')->getAlbumIDs();

		// if $galleries is false, the gallery model returned no data.
		// no data means we can stop our work here.
		if(!$albums)
			return false;

		// only pictures from galleries the user can access
		$this->setField('FLOOR(RAND() * COUNT(*)) AS rand_row');
		$this->setFilter('pic.id_album IN ({array_int:albums})');
		$this->addParameter('albums', $albums);

		$rand_row = $this->read('val');

		// we wanne use our model for furher actions, but without the rand stuff
		$this->setField(array(
			'pic.id_picture',
			'pic.title',
			'pic.description',
			'pic.id_member',
			'pic.picture',
			'CONCAT("' . $this->cfg('url_gallery_upload') . '", "/", album.dir_name, "/", pic.picture) AS src',
		));

		$this->setJoin('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album');

		// only one pic
		$this->setLimit($rand_row, 1);

		$this->read();

		// link to picture detail page
		$this->data->page = Url::factory('gallery_picture', array('id_picture' => $this->data->id_picture))->getUrl();

		return $this->data;
	}

	public function getPicture($id_picture = null)
	{
		// get picture
		$this->setField(array(
			'pic.*',
			'if(mem.real_name, mem.real_name, mem.member_name) AS owner',
			'album.dir_name',
			'CONCAT("' . $this->cfg('url_gallery_upload') . '", "/", album.dir_name, "/", pic.picture) AS src',
		));
		$this->setJoin('members', 'mem', 'LEFT', 'mem.id_member=pic.id_member');
		$this->addJoin('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album');
		$this->setFilter('id_picture = {int:id_picture}', array(
			'id_picture' => $id_picture
		));

		$this->read();

		// get gallerydata
		$this->data->gallery = $this->app->getModel('Album')->getAlbumInfos($this->data->id_album);
		$this->data->filesize = FileIO::convFilesize($this->data->filesize);

		return $this->data;
	}

	function deletePicture($id_picture)
	{
		// load current picture data
		$this->find($id_picture);

		// if fitting accessrights or owner of picture
		if(allowedTo('gallery_picture_delete') || $this->data->id_member == User::getId())
		{
			$this->delete($id_picture);
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
		$this->setFilter('id_album={int:id_album}', array('id_album'=>$id_album));
		$this->delete();
	}

	public function saveUploadedPicture($data)
	{
		// Set posted data to model
		$this->setData($data);

		// Get uploaded picture data
		$uploads = FileIO::getUploads();

		// Handle upload errors
		if ($uploads['error'] !== 0)
		{
			// General upload error
			$this->addError('@', $this->txt('upload_error_' . $uploads['error']));
			return;
		}

		// Mime type not allowed
		if (!isset($this->cfg('upload_mime_types')->{$uploads['type']}))
			$this->addError('@', $this->txt('upload_is_no_image'));

		// Uploadsize > than file size set in config
		if ($uploads['size'] > FileIO::getMaximumFileUploadSize())
			$this->addError('@', $this->txt('upload_error_filesize'));

		// End here on errors.
		if ($this->hasErrors())
			return;

		// Get album path
		$album_path = $this->app->getModel('Album')->getAlbumPath($data->id_album);

		// Cleanup filename
		$img_filename = FileIO::cleanFilename($uploads['name']);

		// We need the pure image name and later the extension
		list($img_name, $extension) = explode('.', $img_filename);

		// This date is uesed for image filename encoding and late on for the
		// gallery record in db
		$date_upload = time();

		// Create the encoded picture by joining the image name with md5()'ed
		// filename, user id and uploaddate
		$uniqe_id = md5($img_filename . User::getId() . $date_upload );

		// Images ares stroed with the code from above to prevent duplicate filenames
		$img_filename = $img_name . '-' . $uniqe_id . '.' . $extension;

		// Full imagae path for moving the uploaded file
		$img_path = $album_path . '/' . $img_filename;

		// Move the tmp image tho the gallery by checking set overwrite config
		$move_ok = FileIO::moveUploadedFile( $uploads['tmp_name'] , $img_path, $this->cfg('upload_no_overwrite') );

		// Was the file moved without errors?
		if (!$move_ok)
		{
			$this->addError('@', sprintf($this->txt('move_upload_failed'), $img_path));
			return;
		}

		// The last file check is to open the image with gd. If this works, we can assume this is an image.
		// If this check fails, the file will be deleted and an error added to the model
		try {
			$img = new SimpleImage($img_path);
		}
		catch (\Exception $e)
		{
			// Damn bastard user!
			unlink($img_path);

			$this->addError('@', sprintf($this->txt('upload_is_no_image'), $img_path));
			return;

		}

		// --------------------------------------------------------------------
		// Reaching this point means we have no errors and the uploaded file
		// is placed in the albums directory.
		// --------------------------------------------------------------------

		// Schould we create a thumbnail?
		if ($this->cfg('thumbnail_use'))
		{
			$thumb_path = $album_path . '/thumbs/' . $img_filename;
			$img->fit_to_width($this->cfg('thumbnail_width'))->save($thumb_path, $this->cfg('thumbnail_quality'));
			$this->data->thumb = 1;
		}

		// Users don't need to set a title on upload. When title is empty and
		// no empty titles are allowed by config the image name will be used instead.
		if (empty($this->data->title) && !$this->cfg('empty_title'))
			$this->data->title = $img_name;

		$this->data->unique_id = $uniqe_id;
		$this->data->filesize = $uploads['size'];
		$this->data->picture = $img_filename;
		$this->data->type = $uploads['type'];
		$this->data->id_member = User::getId();
		$this->data->date_upload = $date_upload;
		$this->data->date_update = $date_upload;

		// Get some infos about the image
		$img_size = getimagesize ( $img_path );

		$this->data->width = $img_size[0];
		$this->data->height = $img_size[1];

		// Save the image data whithout further validation
		$this->save(false);
	}
}

?>