<?php
namespace Web\Apps\Gallery\View;

use Web\Framework\Lib\View;

class Gallery_View_Picture extends View
{

	public function Random()
	{
		// picture
		$html = '
		<div class="app-gallery-rnd-box img-rounded-border">
			<a href="' . $this->vars->picture->page . '">
				<img title="' . $this->vars->picture->title . '" alt="' . $this->vars->picture->title . '" width="100%" class="app-gallery-rnd-img img-responsive" src="' . $this->vars->picture->src . '" />
			</a>
			<div class="app-gallery-rnd-infobox">
				<h3>' . $this->vars->picture->title . '</h3>
			</div>
		</div>';

		return $html;
	}

	public function Index()
	{
		// for better reading of html code
		$txt = $this->vars;
		$picture = $this->vars->picture;

		$html = '
		<div class="app-gallery">
			<h1>' . $picture->title . '</h1>
			<div class="app-gallery-picture">
				<a href="' . $picture->src . '">
					<img class="app-gallery-img img-responsive img-rounded-border" src="' . $picture->src . '" alt="' . $this->vars->picture->title . '" title="' . $this->vars->picture->title . '" />
				</a>
			</div>
			<div class="app-gallery-picturedata small panel panel-default">
				<div class="row">
					<div class="col-sm-4">
						<div class="panel-body">
							<h4>' . $txt->infos . '</h4>';

						if ($picture->description)
							$html .= '
							<p class="app-gallery-picture-text">' . $txt->description . ': <strong>' . $picture->description . '</p>';

						if ($picture->owner)
							$html .= '
							<p class="app-gallery-picture-member">' . $txt->uploader . ': <strong>' . $picture->owner . '</strong></p>';

						if ($picture->gallery->url)
							$html .= '
							<p class="app-gallery-picture-upload">' . $txt->gallery . ': <strong><a href="' . $picture->gallery->url . '">' . $picture->gallery->title . '</a></strong></p>';

							$html .= '
							<p class="app-gallery-picture-upload">' . $txt->date . ': <strong>' . date('Y-m-d H:i', $picture->date_upload) . '</strong></p>
							<p class="app-gallery-picture-size">' . $txt->filesize . ': <strong>' . $picture->filesize . '</strong></p>
							<p class="app-gallery-picture-dimension">' . $txt->dimension . ': <strong>' . $picture->width . ' × ' . $picture->height . ' px</strong></p>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="panel-body">
							<h4>' . $txt->urls . '</h4>
							<p class="app-gallery-src">' . $txt->src . ': <strong><a href="' . $picture->src . '" target="_blank">' . $picture->width . 'px × ' . $picture->height . 'px</a></strong></p>
							<p class="app-gallery-src-thumb">' . $txt->src_thumb . ': <strong><a href="' . $picture->src_thumb .'" target="_blank">Width 320px</a></strong></p>
							<p class="app-gallery-src-medium">' . $txt->src_medium . ': <strong><a href="' . $picture->src_medium . '" target="_blank">Width 720px</a></strong></p>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="panel-body">
							<h4>Navigation</h4>
							<ol class="breadcrumb">
								<li><a href="' . $txt->link_gallery . '">Gallery</a></li>
								<li><a href="' . $picture->gallery->url . '">' . $picture->gallery->title . '</a></li>
								<li class="active">Data</li>
							</ol>
						</div>
					</div>
				</div>
			</div>
		</div>';

		return $html;
	}

}

?>