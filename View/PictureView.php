<?php
namespace Web\Apps\Gallery\View;

use Web\Framework\Lib\View;

class PictureView extends View
{
	public function Random()
	{
		// picture
		echo '
		<div class="app-gallery-rnd-box img-rounded-border">
			<a href="', $this->picture->page, '">
				<img title="', $this->picture->title, '" alt="', $this->picture->title, '" width="100%" class="app-gallery-rnd-img img-responsive" src="', $this->picture->src, '" />
			</a>
			<div class="app-gallery-rnd-infobox">
				<h3>', $this->picture->title, '</h3>
			</div>
		</div>';
	}

	public function Index()
	{
		echo '
		<div class="app-gallery-picture">
			<a href="', $this->picture->src, '">
				<img class="app-gallery-img img-responsive img-rounded-border" src="', $this->picture->src, '" alt="', $this->picture->title, '" title="', $this->picture->title, '" />
			</a>
			<div class="app-gallery-picturedata small panel panel-default">
				<div class="panel-body">
					<h3 class="no-top-margin">', $this->picture->title, '</h3>';

				if (isset($this->picture->description))
					echo '
					<p class="app-gallery-picture-text">', $this->description, ': <strong>', $this->picture->description, '</p>';

				if (isset($this->picture->owner))
					echo '
					<p class="app-gallery-picture-member">', $this->uploader, ': <strong>', $this->picture->owner, '</strong></p>';

				if (isset($this->picture->gallery->url))
					echo '
					<p class="app-gallery-picture-upload">', $this->gallery, ': <strong><a href="', $this->picture->gallery->url, '">', $this->picture->gallery->title, '</a></strong></p>';

					echo '
					<p class="app-gallery-picture-upload">', $this->date, ': <strong>', date('Y-m-d H:i', $this->picture->date_upload), '</strong></p>
					<p class="app-gallery-picture-size">', $this->filesize, ': <strong>', $this->picture->filesize, '</strong></p>
					<p class="app-gallery-picture-dimension">', $this->dimension, ': <strong>', $this->picture->width, ' × ', $this->picture->height, ' px</strong></p>
				</div>
			</div>
		</div>';
	}

	public function Upload()
	{
		echo '<h1>',  $this->upload, ' <small>', $this->headline, '</h1>';
		echo $this->form;
	}
}
?>