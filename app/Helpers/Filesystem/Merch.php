<?php

namespace App\Helpers\Filesystem;

use App\Models\Core\Correspondence as CorrespondenceModel;

class Merch extends Filesystem {
    public static function correspondence_path(CorrespondenceModel $objCorrespondence): string {
        return (
            "correspondence" . self::DS .
            "merch" . self::DS .
            strtolower($objCorrespondence->app->app_name) . self::DS .
            $objCorrespondence->correspondence_uuid
        );
    }
}
