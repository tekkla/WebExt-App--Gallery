<?php
namespace Web\Apps\Gallery\Controller;

use Web\Framework\Lib\Controller;
use Web\Framework\Html\Elements\Form;
use Web\Framework\Html\Elements\Heading;


class Gallery_Controller_Album extends Controller
{
	public function Edit($id_album=null)
	{
		$post = $this->Request->getPost();

		if ($post)
		{
			$this->Model->setData($post)->saveAlbum();

			// save errors?
			if (!$this->Model->hasErrors())
			{
				// 	go to action set by model save action
				$this->Redirect($this->Model->data->action, array('id_album'=>$this->Model->data->id_album));

				return;
			}
		}

		// ---------------------------------------
		// DATA
		// ---------------------------------------

		// load it only if the is no data present
		if (!$this->Model->hasData())
			$this->Model->getEdit($id_album);

		// ------------------------------
		// TEXT
		// ------------------------------
		$this->setVar('headline', $this->Text('cfg_album_headline_' . $this->Model->data->mode));

		// ---------------------------
		// FORM
		// ---------------------------

		// create form object
		$form = Form::factory();

		$params = array(
			'id_album'=> $id_album
		);

		$form->setActionRoute($this->Request->getCurrentRoute(), $params);

		$form->attachModel($this->Model);

		$form->setGridLabel(2);

		// hidden raid id field only on edit
		if (isset($id_album))
		{
			$control = $form->createElement('hidden', 'id_album');
			$control->setValue($id_album);
			$form->addControl($control);
		}

		$control = $form->createElement('hidden', 'mode');
		$form->addControl($control);

		// album infos
		$control = Heading::factory(3);
		$control->setInner('gallery_cfg_album_headline_info');
		$form->addControl($control);

		$control = $form->createElement('text', 'title');
		$control->setLabel('gallery_cfg_album_title');
		$form->addControl($control);

		$control = $form->createElement('textarea', 'description');
		$control->setLabel('gallery_cfg_album_description');
		$control->setCols(40);
		$control->setRows(5);
		$form->addControl($control);

		$control = $form->createElement('text', 'tags');
		$control->setLabel('gallery_cfg_album_tags');
		$form->addControl($control);

		$control = $form->createElement('text', 'notes');
		$control->setLabel('gallery_cfg_album_notes');
		$form->addControl($control);

		$control = $form->createElement('textarea', 'legalinfo');
		$control->setLabel('gallery_cfg_album_legalinfo');
		$control->setCols(40);
		$control->setRows(5);
		$form->addControl($control);

		// accesrights
		$control = Heading::factory(3);
		$control->setInner($this->Text('cfg_album_headline_access'));
		$form->addControl($control);

		// we need the membergroups
		$membergroups = $this->App('Forum')->Model('Membergroups')->getMembergroups();

		// buttons
		$control = $form->createElement('optiongroup', 'accessgroups');
		$control->setLabel('gallery_cfg_album_accessgroups');

		foreach ($membergroups as $group)
		{
			$option = $control->createOption();
			$option->setValue($group->id_group);
			$option->setInner($group->group_name);

			if (isset($this->Model->data->accessgroups->{$group->id_group}))
				$option->isSelected(1);

			$control->addOption($option);

			#$this->Debug($option);
		}

		$form->addControl($control);

		// buttons
		$control = $form->createElement('optiongroup', 'uploadgroups');
		$control->setLabel('gallery_cfg_album_uploadgroups');
		$control->addCss('grid_6');

		foreach ($membergroups as $group)
		{
			$option = $control->createOption();
			$option->setValue($group->id_group);
			$option->setInner($group->group_name);
			$control->addOption($option);

			if (isset($this->Model->data->uploadgroups->{$group->id_group}))
				$option->isSelected(1);

			#$this->Debug($option);
		}

		$form->addControl($control);

		// options

		$control = Heading::factory(3);
		$control->setInner('gallery_cfg_album_headline_options');
		$form->addControl($control);

		$control = $form->createElement('switch', 'scoring');
		$control->setLabel('gallery_cfg_album_scoring');
		$form->addControl($control);

		$control = $form->createElement('switch', 'anonymous');
		$control->setLabel('gallery_cfg_album_anonymous');
		$form->addControl($control);

		$control = $form->createElement('number', 'img_per_user');
		$control->setLabel('gallery_cfg_album_img_per_user');
		$form->addControl($control);


		// save button
		$control = $form->createElement('submit');
		$form->addControl($control);

		$this->setVar('form', $form->Create());

		// puiblish data to view
		$this->setVar('edit', $this->Model->data);
	}

	public function Index($id_album)
	{

		$album = $this->Model->getAlbum($id_album);

		if (!$album)
			return false;

		$this->setPageTitle( $this->Text('headline') . ' - ' . $album->title);
		$this->setPageDescription($album->description);

		// ------------------------------
		// Text
		// ------------------------------
		$this->setVar(array(
			'nopics' => $this->Text('album_nopics'),
			'grid' => $this->Cfg('album_grid'),
			'album' => $album
		));
	}

	public function Gallery()
	{
		$albums = $this->Model->getAlbums();

		if (!$albums)
			return false;

		$this->setPageTitle($this->Text('headline'));
		$this->setPageDescription($this->Text('description'));

		$this->setVar(array(
			'headline' => $this->Text('headline'),
			'intro' => $this->Text('intro'),
			'legal' => $this->Text('legal'),
			'albums' => $albums,
			'nopics' => $this->Text('album_nopics'),
			'grid' => $this->Cfg('album_grid')
		));

	}

	public function Convert()
	{
		$this->Model->convertAlbums();
	}
}
?>