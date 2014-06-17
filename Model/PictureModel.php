<?php
namespace Web\Apps\Gallery\Model;

use	Web\Framework\Lib\Model;
use	Web\Framework\Lib\Url;
use Web\Framework\Lib\User;
use Web\Framework\Lib\FileIO;
use Web\Framework\Tools\SimpleImage\SimpleImage;

/**
 * Album model
 * @author Michael "Tekkla" Zorn <tekkla@tekkla.de>
 * @package App Gallery
 * @subpackage Model/Picture
 * @license BSD
 * @copyright 2014 by author
 */
final class PictureModel extends Model
{
	protected $tbl = 'app_gallery_pictures';
	protected $alias = 'pic';

	/**
	 * Returns a random picture of a specific album
	 * @param int $id_album Id of album
	 * @return Web\Framework\Lib\Data
	 */
	public function getRndAlbumPicture($id_album)
	{
		// get random row
		$rand_row = $this->read(array(
			'type' => 'val',
			'field' => 'FLOOR(RAND() * COUNT(*)) AS rand_row',
			'filter' => 'pic.id_album={int:id_album}',
			'param' => array(
				'id_album' => $id_album
			)
		));

		return $this->read(array(
		    'field' => array(
		    	'pic.id_picture',
		    	'pic.title',
		    	'pic.description',
		    	'pic.id_member',
		    	'pic.picture',
		    	'CONCAT("' . $this->cfg('url_gallery_upload') . '", "/", album.dir_name, "/thumbs/", pic.picture) AS src',
		    ),
		    'join' => array(
		        array('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album'),
		    ),
		    'filter' => 'pic.id_album={int:id_album}',
		    'param' => array(
		    	'id_album' => $id_album
		    ),
		    'limit' => array($rand_row, 1)
		));
	}

	/**
	 * Returns all pictures of a specific album
	 * @param int $id_album Id of album
	 * @return \Web\Framework\Lib\Data
	 */
	public function getAlbumPictures($id_album)
	{
		return $this->read(array(
		    'type' => '*',
		    'field' => array(
		    	'pic.id_picture',
		    	'pic.title',
		    	'pic.description',
		    	'pic.id_member',
		    	'pic.picture',
		    	'CONCAT("' . $this->cfg('url_gallery_upload') . '", "/", album.dir_name, "/thumbs/", pic.picture) AS src',
		    ),
		    'join' => array(
		        array('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album'),
		    ),
		    'filter' => 'pic.id_album={int:id_album}',
		    'param' => array(
		    	'id_album' => $id_album
		    ),
			'order' => 'pic.date_upload DESC',
		), 'processAlbumPicture');
	}

	protected function processAlbumPicture($picture)
	{
		// link to picture detail page
		$picture->url = Url::factory('gallery_picture', array('id_picture' => $picture->id_picture))->getUrl();

		if(!isset($picture->title))
			$picture->title = $this->txt('picture_without_title');

		return $picture;
	}

	/**
	 * Returns a random picture from any album accessible to the user.
	 * Returns false when no picture was found.
	 * @return boolean|\Web\Framework\Lib\Data
	 */
	public function getRndPicture()
	{
		// Get IDs of gallery accessible for this user
		$albums = $this->getModel('Album')->getAlbumIDs();

		// No data means we can stop our work here.
		if(!$albums)
			return false;

		// Only pictures from galleries the user can access
		$rand_row = $this->read(array(
		    'type' => 'val',
		    'field' => array(
		    	'FLOOR(RAND() * COUNT(*)) AS rand_row'
		    ),
		    'filter' => 'pic.id_album IN ({array_int:albums})',
		    'param' => array(
		    	'albums' => $albums
		    ),
		));

		// only one pic
		$this->read(array(
		    'field' => array(
		    	'pic.id_picture',
		    	'pic.title',
		    	'pic.description',
		    	'pic.id_member',
		    	'pic.picture',
		    	'CONCAT("' . $this->cfg('url_gallery_upload') . '", "/", album.dir_name, "/", pic.picture) AS src',
		    ),
		    'join' => array(
		        array('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album'),
		    ),
		    'filter' => 'pic.id_album IN ({array_int:albums})',
		    'param' => array(
		    	'albums' => $albums
		    ),
			'limit' => array($rand_row, 1)
		));

		// Add url for link to picture detail page
		$this->data->page = Url::factory('gallery_picture', array('id_picture' => $this->data->id_picture))->getUrl();

		return $this->data;
	}

	/**
	 * Returns the data and correponding albuminformations of a specific picture
	 * @param int $id_picture
	 * @return \Web\Framework\Lib\Data
	 */
	public function getPicture($id_picture)
	{
		// get picture
		$this->read(array(
		    'field' => array(
		    	'pic.*',
		    	'if(mem.real_name, mem.real_name, mem.member_name) AS owner',
		    	'CONCAT("' . $this->cfg('url_gallery_upload') . '", "/", album.dir_name, "/", pic.picture) AS src',
		    ),
		    'join' => array(
		        array('members', 'mem', 'LEFT', 'mem.id_member=pic.id_member'),
		        array('app_gallery_albums', 'album', 'INNER', 'pic.id_album=album.id_album'),
		    ),
		    'filter' => 'pic.id_picture = {int:id_picture} AND pic.id_album IN ({array_int:albums})',
		    'param' => array(
		    	'id_picture' => $id_picture,
		    	'albums' => $this->getModel('Album')->getAlbumIDs(),
		    ),
		));

		// get gallerydata
		$this->data->album = $this->getModel('Album')->getAlbumInfos($this->data->id_album);
		$this->data->filesize = FileIO::convFilesize($this->data->filesize);

		return $this->data;
	}

	/**
	 * Deletes a specific picture
	 * @param int $id_picture
	 * @return boolean
	 */
	public function deletePicture($id_picture)
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

	/**
	 * Deletes all pictures of a specific user.
	 * If user id is not sent, the id of the current user will be used.
	 * @param int $id_user Optional user id
	 * @return boolean
	 */
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

	/**
	 * Deletes all pictures of a specific album
	 * @param int $id_album Id of album to delete
	 */
	function deleteAlbumPictures($id_album)
	{
		$this->delete(array(
		    'filter' => 'id_album={int:id_album}',
		    'param' => array(
		    	'id_album' => $id_album
		    ),
		));
	}

	/**
	 * Saves uploaded image and creates record in picture table.
	 * @param \Web\Framework\Lib\Data $data Data to save
	 * @param int $id_album id of album the picture is from
	 */
	public function saveUploadedPicture($data, $id_album)
	{
		## Check general upload for errors...

		// Get uploaded picture data
		$uploads = FileIO::getUploads();

		// Handle upload errors
		if ($uploads['error'] !== 0)
		{
			// General upload error
			$this->addError('@', $this->txt('upload_error_' . $uploads['error']));
			return;
		}

		// Load album infos
		$album = $this->getModel('Album')->getAlbum($id_album);

		// Mime type not allowed
		if (!isset($album->mime_types->{$uploads['type']}))
			$this->addError('@', $this->txt('upload_mime_type_not_allowed'));

		// Uploadsize > than file size set in config
		if ($uploads['size'] > FileIO::getMaximumFileUploadSize())
			$this->addError('@', $this->txt('upload_error_filesize'));

		// End here on errors.
		if ($this->hasErrors())
			return;

		## First error check passed. Go on with data processing...

		// Set posted data to model
		$this->data = $data;

		// Add album id
		$this->data->id_album = $id_album;

		// Get album path
		$album_path = $this->getModel('Album')->getAlbumPath($id_album);

		// Cleanup filename
		$img_filename = FileIO::cleanFilename($uploads['name']);

		// We need the pure image name and later the extension
		list($img_name, $extension) = explode('.', $img_filename);

		// This date is uesed for image filename encoding and late on for the
		// gallery record in db
		$date_upload = time();

		// Create a unique picture id by md5()ing the combined image filename, user id and uploaddate
		$uniqe_id = md5($img_filename . User::getId() . $date_upload );

		// Images ares stroed with the code from above to prevent duplicate filenames
		$img_filename = $img_name . '-' . $uniqe_id . '.' . $extension;

		// Full imagae path for moving the uploaded file
		$img_path = $album_path . '/' . $img_filename;

		// Move the tmp image tho the gallery by checking set overwrite config
		$move_ok = FileIO::moveUploadedFile($uploads['tmp_name'], $img_path);

		// Was the file moved without errors?
		if (!$move_ok)
		{
			$this->addError('@', sprintf($this->txt('move_upload_failed'), $img_path));
			return;
		}

		## Last check is to open the image with gd. If this works, we can assume this is an image.

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

		## Reaching this point means we have no errors and the uploaded file
		## is placed in the albums directory.

		// Should we create a thumbnail?
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
		$img_size = getimagesize($img_path);

		$this->data->width = $img_size[0];
		$this->data->height = $img_size[1];

		// Save the image data whithout further validation
		$this->save(false);
	}
}
?>
