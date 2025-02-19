<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2016 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

/**
 * Kohana routing rules
 * @package Kohana
 */

$config['_healthcheck'] = 'AppController/healthcheck';
$config['_errors/log'] = 'AppController/logJsException';

// $config['admin/edit/.+'] = 'AdminController/edit/$1';
$config['admin_ajax/widget_settings/([^/]+)'] = 'AdminAjaxController/widgetSettings/$1';
$config['admin_ajax/widget_disp_conds'] = 'AdminAjaxController/widgetDispConds';
$config['admin_ajax/widget_disp_cond_params'] = 'AdminAjaxController/widgetDispCondParams';
$config['admin_ajax/add_addon/([0-9]+)/([^/]+)'] = 'AdminAjaxController/addAddon/$1/$2';
$config['admin_ajax/footer_compat'] = 'AdminAjaxController/footerCompat';
$config['admin_ajax/get_tag_suggestions/([^/]+)'] = 'AdminAjaxController/getTagSuggestions/$1';
$config['admin_ajax/get_tag_suggestions'] = 'AdminAjaxController/getTagSuggestions';
$config['admin_ajax/get_entrance_arguments/([^/]+)'] = 'AdminAjaxController/getEntranceArguments/$1';
$config['admin_ajax/get_entrance_arguments'] = 'AdminAjaxController/getEntranceArguments';
$config['admin_ajax/add_category'] = 'AdminAjaxController/addCategory';
$config['admin_ajax/attr_editor'] = 'AdminAjaxController/attrEditor';
$config['admin_ajax/lnk_editor'] = 'AdminAjaxController/lnkEditor';
$config['admin_ajax/tour_complete/([-_a-zA-Z0-9]+)'] = 'AdminAjaxController/setTourCompleted/$1';
$config['admin_ajax/richtext_import/([^/]+)'] = 'AdminAjaxController/richtextImport/$1';
$config['admin_ajax/richtext_import_iframe'] = 'AdminAjaxController/richtextImportIframe';
$config['admin_ajax/lnk_editor'] = 'AdminAjaxController/lnkEditor';
$config['admin_ajax/style_guide_demo_conditions'] = 'AdminAjaxController/styleGuideDemoConditions';

$config['admin/?'] = 'AdminController/index';
$config['admin/login'] = 'AdminController/login';
$config['admin/login_action'] = 'AdminController/loginAction';
$config['admin/login-two-factor'] = 'AdminController/loginTwoFactor';
$config['admin/login-two-factor-action'] = 'AdminController/loginTwoFactorAction';
$config['admin/login_callback'] = 'AdminController/loginCallback';
$config['admin/logout'] = 'AdminController/logout';
$config['admin/set_richtext/([^/]+)'] = 'AdminController/setRichtext/$1';
$config['admin/style_guide'] = 'AdminController/styleGuide/index';
$config['admin/style_guide/([_a-z]+)'] = 'AdminController/styleGuide/$1';
$config['admin/dashboard'] = 'AdminController/dashboard';
$config['admin/close_firstrun'] = 'AdminController/closeFirstrun';
$config['admin/intro/([^/]+)'] = 'AdminController/intro/$1';
$config['admin/search/([^/]+)'] = 'AdminController/search/$1';
$config['admin/contents/([^/]+)'] = 'AdminController/contents/$1';
$config['admin/ai_reprocess/([^/]+)'] = 'AdminController/aiReprocess/$1';
$config['admin/ai_reprocess_action/([^/]+)'] = 'AdminController/aiReprocessAction/$1';
$config['admin/export/([^/]+)'] = 'AdminController/export/$1';
$config['admin/export_action/([^/]+)'] = 'AdminController/exportAction/$1';
$config['admin/import_upload/([^/]+)'] = 'AdminController/importUpload/$1';
$config['admin/import_upload_action/([^/]+)'] = 'AdminController/importUploadAction/$1';
$config['admin/import_options/([^/]+)'] = 'AdminController/importOptions/$1';
$config['admin/import_action/([^/]+)'] = 'AdminController/importAction/$1';
$config['admin/email_reports/([^/]+)'] = 'AdminController/emailReports/$1';
$config['admin/email_report_add/([^/]+)'] = 'AdminController/emailReportAdd/$1';
$config['admin/email_report_action/([^/]+)'] = 'AdminController/emailReportAction/$1';
$config['admin/email_report_send/([^/]+)'] = 'AdminController/emailReportSend/$1';
$config['admin/add/([^/]+)'] = 'AdminController/add/$1';
$config['admin/add_save/([^/]+)'] = 'AdminController/addSave/$1';
$config['admin/edit/([^/]+)/([0-9]+)'] = 'AdminController/edit/$1/$2';
$config['admin/edit_save/([^/]+)/([0-9]+)'] = 'AdminController/editSave/$1/$2';
$config['admin/delete/([^/]+)/([0-9]+)'] = 'AdminController/delete/$1/$2';
$config['admin/delete_save/([^/]+)/([0-9]+)'] = 'AdminController/deleteSave/$1/$2';
$config['admin/restore/([0-9]+)'] = 'AdminController/restore/$1';
$config['admin/duplicate/([^/]+)/([0-9]+)'] = 'AdminController/duplicate/$1/$2';
$config['admin/duplicate_save/([^/]+)/([0-9]+)'] = 'AdminController/duplicateSave/$1/$2';
$config['admin/moderate'] = 'AdminController/moderate';
$config['admin/moderate_action'] = 'AdminController/moderateAction';
$config['admin/extra/(.+)'] = 'AdminController/extra/$1';
$config['admin/call/([^/]+)/(.+)'] = 'AdminController/call/$1/$2';
$config['admin/set_active_subsite'] = 'AdminController/setActiveSubsite';
$config['admin/ajax_unlock'] = 'AdminController/ajaxUnlock';
$config['admin/user-agent'] = 'AdminController/userAgent';
$config['admin/heartbeat'] = 'AdminController/heartbeat';

$config['content_subscribe/unsub/([0-9]+)/([a-z0-9]+)'] = 'Sprout\\Controllers\\ContentSubscribeController/unsub/$1/$2';
$config['content_subscribe/unsub_action/([0-9]+)/([a-z0-9]+)'] = 'Sprout\\Controllers\\ContentSubscribeController/unsubAction/$1/$2';

$config['dbtools/?'] = 'DbToolsController/index';
$config['dbtools/(.+)'] = 'DbToolsController/$1';

$config['email_share/share'] = 'Sprout\\Controllers\\EmailShareController/share';
$config['email_share/submit'] = 'Sprout\\Controllers\\EmailShareController/submit';
$config['email_share/thanks'] = 'Sprout\\Controllers\\EmailShareController/thanks';

$config['page/view_by_name/([^/]+)'] = 'Sprout\\Controllers\\PageController/viewByName/$1';
$config['page/view_by_id/([0-9]+)'] = 'Sprout\\Controllers\\PageController/viewById/$1';
$config['page/view_specific_rev/([0-9]+)/([0-9]+(?:/[a-zA-Z0-9]+)?)'] = 'Sprout\\Controllers\\PageController/viewSpecificRev/$1/$2';
$config['page/preview_store/([0-9]+)'] = 'Sprout\\Controllers\\PageController/previewStore/$1';
$config['page/preview/([0-9]+)'] = 'Sprout\\Controllers\\PageController/preview/$1';
$config['page/additional_css/([0-9]+)/([^/]+)'] = 'Sprout\\Controllers\\PageController/additionalCss/$1/$2';
$config['page/additional_css/([0-9]+)'] = 'Sprout\\Controllers\\PageController/additionalCss/$1';
$config['page/front_end_search/([0-9]+)/([^/]+)/([^/]+)'] = 'Sprout\\Controllers\\PageController/frontEndSearch/$1/$2/$3';
$config['page/review/([0-9]+)'] = 'Sprout\\Controllers\\PageController/review/$1';

$config['search(?:/(?:index)?)?'] = 'Sprout\\Controllers\\SearchController/index';

$config['file/resize/([^/]+)/([^/]+)'] = 'Sprout\\Controllers\\FileController/resize/$1/$2';
$config['file/redirect_resize/([^/]+)/([^/]+)'] = 'Sprout\\Controllers\\FileController/redirectResize/$1/$2';
$config['file/play_audio/([^/]+)'] = 'Sprout\\Controllers\\FileController/playAudio/$1';
$config['file/resolve/([^/]+)'] = 'Sprout\\Controllers\\FileController/resolve/$1';
$config['file/download/([0-9]+)(?:/([a-z_]+))?'] = 'Sprout\\Controllers\\FileController/download/$1/$2';
$config['file/name_lookup'] = 'Sprout\\Controllers\\FileController/nameLookup';

$config['_media/(.+)'] = 'Sprout\\Controllers\\MediaController/serve/$1';
$config['media_tools/(.+)'] = 'Sprout\\Controllers\\MediaController/$1';

$config['tinymce4/image'] = 'Tinymce4Controller/image';
$config['tinymce4/image_list/([0-9]+)'] = 'Tinymce4Controller/imageList/$1';
$config['tinymce4/image_search'] = 'Tinymce4Controller/imageSearch';
$config['tinymce4/image_size/([0-9]+)'] = 'Tinymce4Controller/imageSize/$1';
$config['tinymce4/library'] = 'Tinymce4Controller/library';
$config['tinymce4/library_search'] = 'Tinymce4Controller/librarySearch';
$config['tinymce4/library_browse/([^/]+)'] = 'Tinymce4Controller/libraryBrowse/$1';
$config['tinymce4/video'] = 'Tinymce4Controller/video';
$config['tinymce4/video_list/([0-9]+)'] = 'Tinymce4Controller/videoList/$1';
$config['tinymce4/video_search'] = 'Tinymce4Controller/videoSearch';
$config['tinymce4/upload'] = 'Tinymce4Controller/upload';
$config['tinymce4/gallery'] = 'Tinymce4Controller/gallery';

$config['cron_job/run/([a-zA-Z0-9_]+)'] = 'CronJobController/run/$1';
$config['cron_job/runJob'] = 'CronJobController/runJob';

$config['worker_job/run/([0-9]+)/([a-zA-Z0-9]+)'] = 'WorkerJobController/run/$1/$2';

$config['locale/get_address_fields/([^/]+)'] = 'LocaleController/getAddressFields/$1';
$config['locale/get_address_fields_required/([^/]+)'] = 'LocaleController/getAddressFieldsRequired/$1';

if (IN_PRODUCTION) {
    $config['robots\.txt'] = 'SeoController/robots';
} else {
    $config['robots\.txt'] = 'SeoController/robotsDeny';
}

$config['seo/xmlSitemap'] = 'SeoController/xmlSitemap';

$config['embed_video/thumb/([^/]+)/([^/]+)/([^/]+)'] = 'EmbedVideoController/thumb/$1/$2/$3';

$config['file_upload/upload_begin'] = 'Sprout\\Controllers\\FileUploadController/uploadBegin';
$config['file_upload/upload_chunk'] = 'Sprout\\Controllers\\FileUploadController/uploadChunk';
$config['file_upload/upload_done'] = 'Sprout\\Controllers\\FileUploadController/uploadDone';
$config['file_upload/upload_form'] = 'Sprout\\Controllers\\FileUploadController/uploadForm';
$config['file_upload/upload_cancel'] = 'Sprout\\Controllers\\FileUploadController/uploadCancel';

$config['captcha/image/([0-9]+)'] = 'Sprout\\Controllers\\CaptchaController/image/$1';
$config['captcha/about'] = 'Sprout\\Controllers\\CaptchaController/about';

$config['advanced_search'] = 'Sprout\\Controllers\\AdvancedSearchController/index';

$config['result/(error|success)'] = 'Sprout\\Controllers\\ResultController/$1';
