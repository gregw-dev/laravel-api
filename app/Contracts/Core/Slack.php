<?php

namespace App\Contracts\Core;

use App\Models\Common\LogError;
use App\Models\Soundblock\Projects\Project as ProjectModel;
use App\Models\Users\User;
use Laravel\Passport\Client;

interface Slack {
    public function githubNotification(string $channel, array $githubPayload): string;
    public function githubActionNotification(string $channel, array $githubPayload): string;
    public function exceptionNotification(LogError $logError): string;
    public function passportNotification(Client $client, array $ebInfo, string $errorMessage);
    public function qldbNotification(string $message, string $host, string $channel): void;
    public function supervisorNotification(string $queue, string $channel): void;
    public function jobNotification(string $message, string $channel, string $jobType, string $jobName, ?User $objUser = null): void;
    public function reportPlatformNotification(string $message, string $channel, string $strJobFileName, string $strReportFileName = null): void;
    public function reportProcessedFileNotification(array $arrProcessedFile, string $channel): void;
    public function reportExchangeRates(string $message): void;
    public function reportAllExchangeRates(string $message): void;
    public function reportMoveFileFails(ProjectModel $objProject): void;
    public function chargeFailedReport(string $strChargeType, string $strAccountName, string $strAccountUuid, string $strPlanType);
    public function chargeFailedExceptionReport(string $strChargeType, string $strAccountName, string $strAccountUuid, string $strPlanType, string $strMessage);
    public function syncLedgerFailedReport(string $strMessage, string $strTable);
}
