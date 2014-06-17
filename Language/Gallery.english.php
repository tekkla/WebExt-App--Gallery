<?php
// Version: 2.1; Gallery

$txt['app_gallery_name'] = 'Gallery';

// Permissions
$txt['permissiongroup_gallery_classic_perm'] = 'WebExt: Gallery';
$txt['permissiongroup_simple_gallery_simple_perm'] = 'WebExt: Gallery';
$txt['permissionname_gallery_perm_manage_album'] = 'Manage album';
$txt['permissionhelp_gallery_perm_manage_album'] = 'Allows create, edit and delete of all albums.';
$txt['permissionname_gallery_perm_manage_image'] = 'Manage pictures';
$txt['permissionhelp_gallery_perm_manage_image'] = 'Allows edit and delete of all pictures.';

// Album
$txt['app_gallery_headline'] = 'Gallery';
$txt['app_gallery_upload'] = 'Upload picture';
$txt['app_gallery_intro'] = '';
$txt['app_gallery_legal'] = '';
$txt['app_gallery_pictures'] = 'Pictures';
$txt['app_gallery_nopics'] = 'No pictures in this album.';
$txt['app_gallery_without_title'] = '<ohne Titel>';
$txt['app_gallery_album'] = 'Album';
$txt['app_gallery_album_headline_info'] = 'Album informations';
$txt['app_gallery_album_title'] = 'Title';
$txt['app_gallery_album_description'] = 'Description';
$txt['app_gallery_album_category'] = 'Album group';
$txt['app_gallery_album_tags'] = 'Tags';
$txt['app_gallery_album_notes'] = 'Internal notes';
$txt['app_gallery_album_legalinfo'] = 'Legal informations';
$txt['app_gallery_album_new'] = 'New album';
$txt['app_gallery_album_edit'] = 'Edit album';
$txt['app_gallery_album_delete'] = 'Delete album';
$txt['app_gallery_album_headline_upload'] = 'Upload';
$txt['app_gallery_album_upload_not_active'] = 'Upload is inactive because of not selected MIME types in app config';
$txt['app_gallery_album_mime_types'] = 'Allowed mime types';
$txt['app_gallery_mime_type_help'] = 'Select MIME-types allowed to be uploaded. If none is selected, the upload will be deactivated.';
$txt['app_gallery_album_headline_access'] = 'Userrights';
$txt['app_gallery_album_accessgroups'] = 'View album';
$txt['app_gallery_accessgroups_help'] = 'Selected groups are allowed to see this album. No selected group means to hide this album from public (except admins and owner)';
$txt['app_gallery_album_uploadgroups'] = 'Upload pictures';
$txt['app_gallery_uploadroups_help'] = 'Selected groups are allowed to upload pictures.';
$txt['app_gallery_album_headline_options'] = 'Options';
$txt['app_gallery_album_anonymous'] = 'Anonym pictures';
$txt['app_gallery_album_scoring'] = 'Scoring active?';
$txt['app_gallery_album_img_per_user'] = 'Pictures per user';

// Errors
$txt['app_gallery_album_error_title_already_exists'] = 'This album title is already in use.';

// Display
$txt['app_gallery_rnd_image'] = 'Random picture';
$txt['app_gallery_title'] = 'Title';
$txt['app_gallery_description'] = 'Description';
$txt['app_gallery_from_gallery'] = 'From gallery';
$txt['app_gallery_picturedata'] = 'Picture infos';
$txt['app_gallery_filesize'] = 'Filesize';
$txt['app_gallery_uploader'] = 'Uploader';
$txt['app_gallery_dimension'] = 'Sizes';
$txt['app_gallery_date_upload'] = 'Date/Time';
$txt['app_gallery_gallerydata'] = 'Gallery infos';
$txt['app_gallery_imgurl'] = 'URLs';
$txt['app_gallery_imgurl_original'] = 'Orginal';
$txt['app_gallery_imgurl_medium'] = 'Medium';
$txt['app_gallery_imgurl_thumb'] = 'Thumb';
$txt['app_gallery_optional'] = 'Optional';

// Upload
$txt['app_gallery_optional_info'] = 'Feel free to provide a title and a description for the picture. It\'s both optional. If no name is provided the name of the uploaded file will be taken as title.';
$txt['app_gallery_picture_id_album'] = 'Gallery';
$txt['app_gallery_picture_upload'] = 'Upload picture';
$txt['app_gallery_picture_title'] = 'Title';
$txt['app_gallery_picture_description'] = 'Description';
$txt['app_gallery_max_upload_size'] = 'max Filesize: %s (%d Bytes)';
$txt['app_gallery_upload_error_0'] = 'There is no error, the file uploaded with success';
$txt['app_gallery_upload_error_1'] = 'Fileszie of uploaded file exceeds allowed filesize.';
$txt['app_gallery_upload_error_2'] = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
$txt['app_gallery_upload_error_3'] = 'Upload hasn\'t been finished.';
$txt['app_gallery_upload_error_4'] = 'No file uploaded.';
$txt['app_gallery_upload_error_6'] = 'Temp folder couldn\'t be found';
$txt['app_gallery_file_already_exists'] = 'The file "%s" already exists.';
$txt['app_gallery_upload_is_no_image'] = 'The uploaded file is no image.';
$txt['app_gallery_upload_mime_type_not_allowed'] = 'The MIME-Type of uploaded file was not accepted.';

// Config
$txt['app_gallery_cfg_group_display'] = 'Display';
$txt['app_gallery_cfg_grid'] = 'Grid size';
$txt['app_gallery_cfg_grid_desc'] = 'Size of grid on gallery and album index. Select 1 to deactivate grid view.';
$txt['app_gallery_cfg_group_upload'] = 'Upload settings';
$txt['app_gallery_cfg_upload_mime_types'] = 'Mime-Types';
$txt['app_gallery_cfg_path'] = 'Gallery directory';
$txt['app_gallery_cfg_path_desc'] = 'Directory of gallery inside SMF folder.';
$txt['app_gallery_cfg_upload_mime_types_desc'] = 'Select the MIME-types which are allowed to be uploaded. This selection is the general setting which MIMI-type are uploadable in the galleries. Without any selection, uploads will be turned off gallery wide.';
$txt['app_gallery_cfg_group_thumbnail'] = 'Thumbnails';
$txt['app_gallery_cfg_thumbnail_use'] = 'Use thumbnails';
$txt['app_gallery_cfg_thumbnail_use_desc'] = 'When active a thumbnail of an uploaded image will be created with the settings below.';
$txt['app_gallery_cfg_thumbnail_width'] = 'Thumbnail width';
$txt['app_gallery_cfg_thumbnail_width_desc'] = 'Defines the with of the thumbnail to create in pixel.';
$txt['app_gallery_cfg_thumbnail_quality'] = 'Thumbnail JPEG quality (1-100)%';
$txt['app_gallery_cfg_thumbnail_quality_desc'] = 'Defines quality of thumbnail. The value represents the percentage used for jpeg compression.';
?>
