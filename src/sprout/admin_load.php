<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */
use Sprout\Helpers\Register;


// Core admin controllers.
Register::adminControllers(null, [
    'action_log' => \Sprout\Controllers\Admin\ActionLogAdminController::class,
    'content_subscription' => \Sprout\Controllers\Admin\ContentSubscriptionAdminController::class,
    'cron_job' => \Sprout\Controllers\Admin\CronJobAdminController::class,
    'document_type' => \Sprout\Controllers\Admin\DocumentTypeAdminController::class,
    'email_text' => \Sprout\Controllers\Admin\EmailTextAdminController::class,
    'extra_page' => \Sprout\Controllers\Admin\ExtraPageAdminController::class,
    'file' => \Sprout\Controllers\Admin\FileAdminController::class,
    'file_category' => \Sprout\Controllers\Admin\FileCategoryAdminController::class,
    'my_settings' => \Sprout\Controllers\Admin\MySettingsAdminController::class,
    'operator' => \Sprout\Controllers\Admin\OperatorAdminController::class,
    'operator_category' => \Sprout\Controllers\Admin\OperatorCategoryAdminController::class,
    'page' => \Sprout\Controllers\Admin\PageAdminController::class,
    'per_record_permission' => \Sprout\Controllers\Admin\PerRecordPermissionAdminController::class,
    'redirect' => \Sprout\Controllers\Admin\RedirectAdminController::class,
    'redirect_category' => \Sprout\Controllers\Admin\RedirectCategoryAdminController::class,
    'subsite' => \Sprout\Controllers\Admin\SubsiteAdminController::class,
    'site_settings' => \Sprout\Controllers\Admin\SiteSettingAdminController::class,
    'tag' => \Sprout\Controllers\Admin\TagAdminController::class,
    'worker_job' => \Sprout\Controllers\Admin\WorkerJobAdminController::class,
]);