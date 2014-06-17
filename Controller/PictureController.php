<?php
namespace Web\Apps\Gallery\Controller;

use Web\Framework\Lib\Controller;
use Web\Framework\Lib\Url;
use Web\Framework\Lib\Context;
use Web\Framework\Lib\FileIO;
use Web\Framework\Helper\FormDesigner;

/**
 * Album controller
 * @author Michael "Tekkla" Zorn <tekkla@tekkla.de>
 * @package App Gallery
 * @subpackage Controller/Album
 * @license BSD
 * @copyright 2014 by author
 * @todo Image edit not working
 */
class PictureController extends Controller
{
	public function Index($id_picture)
	{
		// load image data
		$picture = $this->model->getPicture($id_picture);

		// if $data == false, the model returned a no access flag and we end the fun here and now
		if (!$picture)
			return false;

		// still here? okay, you have access to gallery
		$this->setVar(array(
			'picture' => $picture,
			'headline' => $this->txt('headline'),
			'infos' => $this->txt('picturedata'),
			'description' => $this->txt('description'),
			'uploader' => $this->txt('uploader'),
			'date' => $this->txt('date_upload'),
			'gallery' => $this->txt('from_gallery'),
			'filesize' => $this->txt('filesize'),
			'dimension' => $this->txt('dimension'),
			'urls' => $this->txt('imgurl'),
			'src' => $this->txt('imgurl_original'),
			'src_thumb' => $this->txt('imgurl_thumb'),
			'src_medium' => $this->txt('imgurl_medium'),
		));

		// Add gallery link to linktree
		$this->addLinktree($this->txt('headline'), Url::factory('gallery_album_index')->getUrl());

		// Add album to linktree
		$this->addLinktree($this->model->data->album->title, Url::factory('gallery_album_album', array('id_album'=>$picture->id_album))->getUrl());

		// Add current album to linktree
		$this->addLinktree($picture->title);
	}

	public function Random()
	{
		$image = $this->model->getRndPicture();

		// if $data == false, the model returned no data.
		// in this case we use the access flag to prevent further controller actions
		if ($image == false)
			return false;

		// still here? okay, you have access to gallery
		$this->setVar(array(
			'headline' => $this->txt('rnd_image'),
			'picture' => $image
		));
	}

	public function Edit($id_picture=null)
	{
		$post = $this->request->getPost();

		if ($post)
		{
			$this->model->setData($post)->savePicture();

			// save errors?
			if (!$this->model->hasErrors())
			{
				// 	go to action set by model save action
				$this->redirect($this->model->data->action, array('id_picture'=>$this->model->data->id_picture));
				return;
			}
		}

		// ---------------------------------------
		// DATA
		// ---------------------------------------

		// load it only if the is no data present
		if (!$this->model->hasData())
			$this->model->getEdit($id_picture);

		// ------------------------------
		// TEXT
		// ------------------------------
		$this->setVar('headline', $this->txt('cfg_picture_headline_' . $this->model->data->mode));

		// ---------------------------
		// FORM
		// ---------------------------

		// Use FormDesigner
		$form = new FormDesigner();

		// With this model
		$form->attachModel($this->model);

		// some global params
		$params = array(
			'id_picture'=> $id_picture
		);

		$form->setActionRoute($this->request->getCurrentRoute(), $params);
		$form->attachModel($this->model);
		$form->setGridLabel(2);

		// hidden picture id field only on edit
		if (isset($id_picture))
			$form->createElement('hidden', 'id_picture')->setValue($id_picture);

		$form->createElement('hidden', 'mode');

		// album infos
		$form->createElement('h3', $this->txt('cfg_album_headline_info'));
		$form->createElement('text', 'title');
		$form->createElement('textarea', 'description');
		$form->createElement('text', 'tags');
		$form->createElement('text', 'notes');
		$form->createElement('textarea', 'legalinfo');

		// save button
		$form->createElement('submit');

		$this->setVar('form', $form);

		// puiblish data to view
		$this->setVar('edit', $this->model);
	}

	public function Upload($id_album)
	{
		// Load album infos
		$album = $this->getModel('Album')->getAlbum($id_album);

		// Check for allowed upload
		if (!isset($album->mime_types))
		{
			$this->addMessage($this->txt('album_upload_not_active'), 'danger');
			$this->redirect(Url::factory('gallery_album_album', array($id_album => $id_album))->getUrl());
		}

		// Get posted data
		$post = $this->request->getPost();

		// Save posted data
		if ($post)
		{
			$this->model->saveUploadedPicture($post, $id_album);

			if (!$this->model->hasErrors())
			{
				redirectexit(Url::factory('gallery_picture', array('id_picture' => $this->model->data->id_picture))->getUrl());
				return;
			}
		}

		// Some texts
		$this->setVar(array(
			'headline' => $this->txt('headline'),
			'title' => $album->title,
			'album_info' => $album->description,
			'upload' => $this->txt('upload'),
			'upload_info' => $this->txt('upload_info'),
			'optional_info' => $this->txt('optional_info')
		));

		// Uploadform by FormDesigner
		$form = new FormDesigner();
		$form->attachModel($this->model);

		// Create form post action
		$form->setActionRoute($this->request->getCurrentRoute(), array('id_album' => $id_album));

		// Album selection dropdown

		// Load list of albums
		$albums = $this->getModel('Album')->getAlbumList();

		// @todo Define exit strategy when use has no access on galleries
		if (!$albums)
			return false;

		/* @var $control \Web\Framework\Html\Form\Select */
		$control = $form->createElement('select', 'id_album');

		foreach ($albums as $album)
			$control->newOption($album->id_album, $album->title, $id_album == $album->id_album ? 1 : 0);

		// Get the maximum file size for posts
		$max_upload_size = FileIO::getMaximumFileUploadSize();

		// File upload field
		$control = $form->createElement('file', 'upload');

		$description = sprintf($this->txt('max_upload_size'), FileIO::convFilesize($max_upload_size), $max_upload_size);

		$mime_types = function($album) {
			$out = array();
			foreach ($album->mime_types as $type)
				$out[] = str_replace('_', '/', $type);

			return $out;
		};

		$description .= ' | MIME-Types : ' . implode(', ', $mime_types($album));

		$control->setDescription($description);

		// Optional picture infos
		$form->createElement('h3', $this->txt('optional'));
		$form->createElement('p', $this->txt('optional_info'));
		$form->createElement('text', 'title');
		$form->createElement('textarea', 'description');

		$this->setVar('form', $form);

		// Add gallery link to linktree
		Context::addLinktree($this->txt('headline'), Url::factory('gallery_album_index')->getUrl());

		// Add current album to linktree
		Context::addLinktree($this->txt('upload'));


	}
}

?>