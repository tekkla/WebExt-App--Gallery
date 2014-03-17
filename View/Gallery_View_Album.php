<?php
namespace Web\Apps\Gallery\View;

use Web\Framework\Lib\View;

class Gallery_View_Album extends View
{

	public function Gallery()
	{
		$html = '
		<div class="app-gallery-index">
			<h1>' . $this->headline . '</h1>
		<p class="lead">' . $this->intro . '</p>
		</div>';

	if ($this->grid != 12)
		$html .= '
		<div class="row">';

		foreach($this->albums as $album)
		{
			// html
			$html .= '
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
		$html .= '
		</div>';

		$html .= '
		<p class="small">' . $this->legal . '</p>';

		return $html;

	}

	public function Index()
	{

		$html = '
		<h2>' . $this->album->title . '</h2>
		<p class="app-gallery-info">' . $this->album->description . '</p>
		<p class="app-gallery-links" style="margin: 1em 0;">' . implode(' | ', $html_links) . '</p>';

	// any pictures to show?
	if(!$this->album->pictures)
		$html .= '
		<p class="app-gallery-nopics">' . $this->nopics . '</p>';

	if($this->album->pictures)
	{
		$html .= '
		<div class="app-gallery-pictures row">';

		// show pictures
		foreach($this->album->pictures as $picture)
		{
			$html .= '
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

		$html .= '
		</div>
		<p class="gallery_links" style="margin: 1em 0;">' . implode(' | ', $html_links) . '</p>
		<p class="small">' . $this->album->legal . '</p>';

		return $html;

	}

	public function Edit()
	{

		global $context;

		$html = '';

		$html = $this->form;

		return $html;
	}
}
?>