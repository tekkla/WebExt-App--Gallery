<?php
namespace Web\Apps\Gallery\Controller;

use Web\Framework\Lib\Controller;
use Web\Framework\Lib\Context;
use Web\Framework\Lib\Url;
use Web\Framework\Lib\App;
use Web\Framework\Helper\FormDesigner;
use Web\Framework\Html\Controls\UiButton;
use Web\Framework\Html\Controls\ButtonGroup;

/**
 * Album controller
 * @author Michael "Tekkla" Zorn <tekkla@tekkla.de>
 * @package App Gallery
 * @subpackage Controller/Album
 * @license BSD
 * @copyright 2014 by author
 */
final class AlbumController extends Controller
{
	public function Edit($id_album=null)
	{
		$post = $this->request->getPost();

		if ($post)
		{
			$this->model->saveAlbum($post);

			// save errors?
			if (!$this->model->hasErrors())
			{
				// 	go to action set by model save action
				$this->addMessage($this->txt('album_config_saved'), 'success');
				redirectexit( Url::factory( $this->request->getCurrentRoute() , array('id_album' => $this->model->data->id_album))->getUrl() );
			}
		}

		// ---------------------------------------
		// DATA
		// ---------------------------------------

		// load it only if the is no data present
		if (!$this->model->hasData())
			$this->model->getEdit($id_album);

		// No model data means the user has no accessright to edit/add albums
		if (!$this->model->hasData())
		{	$url = isset($id_album) ? Url::factory( 'gallery_album_album', array('id_album' => $id_album)) : Url::factory( 'gallery_album_index');
			redirectexit($url->getUrl());
		}

		// ------------------------------
		// TEXT
		// ------------------------------
		$this->setVar(array(
			'title' => $this->txt('album_' . $this->model->data->mode),
			'headline' => $this->txt('headline'),
		));


		// ---------------------------
		// FORM
		// ---------------------------

		// create form object
		$form = new FormDesigner();

		$form->attachModel($this->model);

		$params = array(
			'id_album'=> $id_album
		);

		$form->setActionRoute($this->request->getCurrentRoute(), $params);

		// hidden raid id field only on edit
		if (isset($id_album))
			$form->createElement('hidden', 'id_album');

		$form->createElement('hidden', 'mode');

		// album infos
		$form->openGroup('album_infos');

		if ($id_album === null)
		{
			$form->createElement('h3', $this->txt('album_headline_info'));
			$form->createElement('text', 'title');
		}
		else
			$form->createElement('h2', '<small>' . $this->txt('album') . '</small><br>' . $this->model->data->title);

		$form->createElement('textarea', 'description');
		$form->createElement('text', 'category');
		$form->createElement('text', 'tags');
		$form->createElement('textarea', 'notes');
		$form->createElement('textarea', 'legalinfo');

		// accesrights
		$form->openGroup('album_upload');

		$form->createElement('h3', $this->txt('album_headline_upload'));

		// Get mimetypes from app cfg
		$mime = $this->cfg('upload_mime_types');

		// If we got no mime types, show info that upload is disabled by app config
		if (!$mime)
		{
			$control = $form->createElement('p', $this->txt('album_upload_not_active'));
			$control->addCss('text-danger');
		}
		else
		{
			$control = $form->createElement('optiongroup', 'mime_types');

			foreach ($mime as $mime_type)
			{
				/* @var $option \Web\Framework\Html\Form\Option */
				$option = $control->createOption();
				$option->setValue($mime_type);
				$option->setInner($mime_type);

				if (isset($this->model->data->mime_types->{$mime_type}))
					$option->isSelected(1);
			}

			$control->setDescription($this->txt('mime_type_help'));
		}

		// accesrights
		$form->openGroup('album_access');

		$form->createElement('h3', $this->txt('album_headline_access'));

		// we need the membergroups
		$membergroups = App::create('Forum')->getModel('membergroups')->getMembergroups();

		// Create an optiongroup
		$control = $form->createElement('optiongroup', 'accessgroups');

		$control->addCss('col-sm-6');

		// Add accessgroups description
		$control->setDescription($this->txt('accessgroups_help'));

		// Add membergroups as options
		foreach ($membergroups as $id_group => $group_name)
		{
			/* @var $option \Web\Framework\Html\Form\Option */
			$option = $control->createOption();
			$option->setValue($id_group);
			$option->setInner($group_name);

			if (isset($this->model->data->accessgroups->{$id_group}))
				$option->isSelected(1);
		}

		// buttons
		/* @var $control Web\Framework\Html\Controls\Optiongroup */
		$control = $form->createElement('optiongroup', 'uploadgroups');

		// Add uploadgroups description
		$control->setDescription($this->txt('uploadgroups_help'));

		$control->addCss('col-sm-6');

		foreach ($membergroups as $id_group => $group_name)
		{
			/* @var $option Web\Framework\Html\Form\Option */
			$option = $control->createOption();
			$option->setValue($id_group);
			$option->setInner($group_name);

			if (isset($this->model->data->uploadgroups->{$id_group}))
				$option->isSelected(1);
		}

		// options
		$form->openGroup('album_options');
		$form->createElement('h3', $this->txt('album_headline_options'));
		$form->createElement('switch', 'scoring');
		$form->createElement('switch', 'anonymous');
		$form->createElement('number', 'img_per_user')->addAttribute('min', 0);

		$this->setVar('form', $form);

		// puiblish data to view
		$this->setVar('edit', $this->model);
	}

	public function Index($id_album)
	{
		$album = $this->model->getAlbum($id_album);

		if (!$album)
			redirectexit(Url::factory('gallery_album_index')->getUrl());

		$this->setPageTitle( $this->txt('headline') . ' - ' . $album->title);
		$this->setPageDescription($album->description);

		$this->setVar(array(
			'headline' => $this->txt('headline'),
			'nopics' => $this->txt('album_nopics'),
			'grid' => $this->cfg('grid'),
			'album' => $album,
		));


		if ($album->allow_upload || $album->allow_edit)
		{
			$button_group = new ButtonGroup();
			$button_group->addCss('pull-right');

			if ($album->allow_upload && isset($this->model->data->mime_types))
				$button_group->addButton(
					UiButton::routeButton(
						'gallery_picture_upload',
						array('id_album' => $id_album)
					)
					->setIcon('upload')
					->setText($this->txt('picture_upload'))
				);

			if ($album->allow_edit)
			{
				$button_group->addButton(
					UiButton::routeButton(
						'gallery_album_edit',
						array('id_album' => $id_album)
					)
					->setIcon('edit')
					->setText($this->txt('album_edit'))
				);

				$button_group->addButton(
					UiButton::routeButton(
						'gallery_album_delete',
						array('id_album' => $id_album)
					)
					->setIcon('trash-o')
					->setText($this->txt('album_delete'))
					->setConfirm($this->txt('album_delete'))
					->addCss('pull-right')
				);

			}

			$this->setVar('buttons', $button_group);
		}

		// Add gallery link to linktree
		Context::addLinktree($this->txt('headline'), Url::factory('gallery_album_index')->getUrl());

		// Add current album to linktree
		Context::addLinktree($album->title);
	}

	public function Gallery()
	{
		$albums = $this->model->getAlbums();

		if (!$albums)
			return false;

		$this->setPageTitle($this->txt('headline'));
		$this->setPageDescription($this->txt('description'));

		$this->setVar(array(
			'headline' => $this->txt('headline'),
			'intro' => $this->txt('intro'),
			'legal' => $this->txt('legal'),
			'albums' => $albums,
			'nopics' => $this->txt('album_nopics'),
			'grid' => $this->cfg('grid')
		));

		// Create add gallery buttons for gallery admins
		if ($this->checkAccess('gallery_admin'))
			$this->setVar('btn_add', UiButton::routeButton('gallery_album_new')->setIcon('plus')->setText($this->txt('album_new'))->addCss('pull-right'));

		// Add gallery link to linktree
		Context::addLinktree($this->txt('headline'));
	}

	public function Convert()
	{
		$this->model->convertAlbums();
	}


	public function Delete($id_album)
	{
		$this->model->deleteAlbum($id_album);

		if ($this->model->hasErrors())
		{
			$this->addMessage('Delete error', 'error');
			redirectexit(Url::factory('gallery_album_album', array('id_album' => $id_album)));
		}
		else
		{
			$this->addMessage('Delete success', 'success');
			redirectexit(Url::factory('gallery_album_index')->getUrl());
		}

	}
}
?>