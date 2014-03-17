<?php
namespace Web\Apps\Gallery\Controller;

use Web\Framework\Lib\Controller;
use Web\Framework\Lib\Url;
use Web\Framework\Html\Elements\Form;
use Web\Framework\Html\Elements\Heading;

class Gallery_Controller_Picture extends Controller
{
	public function Index($id_picture)
	{
		// load image data
		$image = $this->Model->getPicture($id_picture);

		// if $data == false, the model returned a no access flag and we end the fun here and now
		if (!$image)
			return false;

		// still here? okay, you have access to gallery
		$this->setVar(array(
			'picture' => $image,
			'headline' => $this->Text('headline'),
			'infos' => $this->Text('picturedata'),
			'description' => $this->Text('description'),
			'uploader' => $this->Text('uploader'),
			'date' => $this->Text('date_upload'),
			'gallery' => $this->Text('from_gallery'),
			'filesize' => $this->Text('filesize'),
			'dimension' => $this->Text('dimension'),
			'urls' => $this->Text('imgurl'),
			'src' => $this->Text('imgurl_original'),
			'src_thumb' => $this->Text('imgurl_thumb'),
			'src_medium' => $this->Text('imgurl_medium'),

			// create some navigation links
			'link_gallery' => Url::factory('gallery_album_index')->getUrl(),
			'link_album' => Url::factory('gallery_album_album', array('id_album'=>$image->id_album))->getUrl(),
		));
	}

	public function Random()
	{
		$image = $this->Model->getRndPicture();

		// if $data == false, the model returned no data.
		// in this case we use the access flag to prevent further controller actions
		if ($image == false)
			return false;

		// still here? okay, you have access to gallery
		$this->setVar(array(
			'headline' => $this->Text('rnd_image'),
			'picture' => $image
		));
	}

	public function Edit($id_picture=null)
	{
		$post = $this->Request->getPost();

		if ($post)
		{
			$this->Model->setData($post)->savePicture();

			// save errors?
			if (!$this->Model->hasErrors())
			{
				// 	go to action set by model save action
				$this->Redirect($this->Model->data->action, array('id_picture'=>$this->Model->data->id_picture));
				return;
			}
		}

		// ---------------------------------------
		// DATA
		// ---------------------------------------

		// load it only if the is no data present
		if (!$this->Model->hasData())
			$this->Model->getEdit($id_picture);

		// ------------------------------
		// TEXT
		// ------------------------------
		$this->setVar('headline', $this->Text('cfg_picture_headline_' . $this->Model->data->mode));

		// ---------------------------
		// FORM
		// ---------------------------

		// create form object
		$form = Form::factory();

		$params = array(
			'id_picture'=> $id_picture
		);

		$form->setActionRoute($this->Request->getCurrentRoute(), $params);

		$form->attachModel($this->Model);

		$form->setGridLabel(2);

		// hidden raid id field only on edit
		if (isset($id_picture))
		{
			$control = $form->createElement('hidden', 'id_picture');
			$control->setValue($id_picture);
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
		$control->addCss('web_max_width');
		$form->addControl($control);

		$control = $form->createElement('textarea', 'description');
		$control->setLabel('gallery_cfg_album_description');
		$control->addCss('web_max_width');
		$control->setCols(40);
		$control->setRows(5);
		$form->addControl($control);

		$control = $form->createElement('text', 'tags');
		$control->setLabel('gallery_cfg_album_tags');
		$control->addCss('web_max_width');
		$form->addControl($control);

		$control = $form->createElement('text', 'notes');
		$control->setLabel('gallery_cfg_album_notes');
		$control->addCss('web_max_width');
		$form->addControl($control);

		$control = $form->createElement('textarea', 'legalinfo');
		$control->setLabel('gallery_cfg_album_legalinfo');
		$control->setCols(40);
		$control->setRows(5);
		$control->addCss('web_max_width');
		$form->addControl($control);

		// accesrights

		$control = Heading::factory(3);
		$control->setInner($this->Text('cfg_album_headline_access'));
		$form->addControl($control);

		// we need the membergroups
		$membergroups = $this->App('Smf')->Model('Membergroups')->getMembergroups();

		// buttons
		$control = $form->createElement('optiongroup', 'accessgroups');
		$control->setLabel('gallery_cfg_album_accessgroups');
		$control->addCss('grid_6');

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
}

?>