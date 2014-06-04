<?php
namespace Web\Apps\Gallery\View;

use Web\Framework\Lib\View;

class AlbumView extends View
{
	public function Gallery()
	{
		echo '
		<div class="app-gallery-index">
			<h1>' . $this->headline . '</h1>';

		if (isset($this->btn_add))
			echo $this->btn_add;

		echo '
		<p class="lead">' . $this->intro . '</p>';

		if ($this->grid != 12)
			echo '
			<div class="row">';

		foreach($this->albums as $album)
		{
			// html
			echo '
			<div class="app-gallery-box' . ($this->grid != 12 ? ' col-sm-' . $this->grid : '') . '">
				<a href="' . $album->url . '" title="' . $album->description . '">
					<div class="app-gallery-titlebox">
						<h3 class="app-gallery-title">' . $album->title . '</h3>
					</div>
					<div class="app-gallery-preview img-rounded-border" style="background-image: url(' . $album->image->src . ');">&nbsp;</div>
				</a>
			</div>';
		}

		if ($this->grid != 12)
			echo '
			</div>';

		echo '
		<p class="small">' . $this->legal . '</p>
		</div>';
	}

	public function Index()
	{
		if (isset($this->buttons))
			echo $this->buttons;

		echo '
		<h1>' . $this->album->title . ' <small>', $this->headline, '</small></h1>';

		if (isset($this->album->description))
			echo '<p class="lead app-gallery-info">' . $this->album->description . '</p>';

		// any pictures to show?
		if(!isset($this->album->pictures))
		{
			echo '<p class="app-gallery-nopics">' . $this->nopics . '</p>';
		}
		else
		{
			echo '<div class="app-gallery-pictures row">';

			// show pictures
			foreach($this->album->pictures as $picture)
			{
				echo '
				<div class="app-gallery-box col-sm-' . $this->grid . '">
					<a href="' . $picture->url . '" class="imglink">
						<div class="app-gallery-titlebox">
							<h3>' . $picture->title . '</h3>
						</div>
						<span class="app-gallery-preview img-rounded-border" style="background-image: url(' . $picture->src . ');"></span>
					</a>
				</div>';
			}
		}

		echo '
		</div>
		<p class="small">' . $this->album->legalinfo . '</p>';
	}

	public function Edit()
	{
		echo '<h1>', $this->title, ' <small>', $this->headline , '</small></h2>';
		echo $this->form;
	}
}
?>