<?php

namespace App\Services\Core;

use App\Contracts\Core\Slack as SlackContract;
use App\Exceptions\Core\Disaster\SlackException;
use App\Models\Common\LogError;
use App\Models\Users\User;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Models\Soundblock\Projects\Project as ProjectModel;

class Slack implements SlackContract {
    const HOST = "https://slack.com/api/";
    const POST_MESSAGE_URI = "chat.postMessage";

    const GITHUB_ACTION_STATUS = [
        "success"   => [
            "color"   => "#2f9e4d",
            "message" => ":white_check_mark: Succeeded Github Action.",
        ],
        "failure"   => [
            "color"   => "#9e2b2b",
            "message" => ":x: Failed Github Action.",
        ],
        "cancelled" => [
            "color"   => "#636363",
            "message" => ":grey_exclamation: Cancelled Github Action.",
        ],
    ];

    const GITHUB_PROFILE_URL = "https://github.com/%s";
    const GITHUB_COMMIT_URI = "/commit/%s";
    const GITHUB_ACTION_URI = "/commit/%s/checks";

    /**
     * @var Client
     */
    private Client $http;

    public function __construct() {
        $this->http = new Client([
            "base_uri" => self::HOST,
            "headers"  => [
                "Authorization" => "Bearer " . config("slack.auth_token"),
            ],
        ]);
    }

    /**
     * @param string $channel
     * @param array $githubPayload
     * @return string
     * @throws SlackException
     */
    public function githubNotification(string $channel, array $githubPayload): string {
        if (!empty($githubPayload["commits"]) && !$githubPayload["deleted"]) {
            try {
                $response = $this->http->post(self::POST_MESSAGE_URI, [
                    "json" => [
                        "channel" => $channel,
                        "text" => null,
                        "attachments" => [$this->buildCommitMessageBody($githubPayload)],
                    ],
                ]);
            } catch (\Exception $exception) {
                throw new SlackException($exception->getMessage(), $exception->getCode(), $exception);
            }

            $arrResponse = json_decode($response->getBody()->getContents(), true);

            if (!$arrResponse["ok"]) {
                throw new SlackException($arrResponse["error"] ?? "Something Went Wrong.", 400);
            }

            return $arrResponse["ts"];
        }

        return ("");
    }

    /**
     * @param array $githubPayload
     * @return array
     */
    private function buildCommitMessageBody(array $githubPayload) {
        $arrCommits = [];
        $arrRef = explode("/", $githubPayload["ref"]);
        $strBranchName = $arrRef[count($arrRef) - 1];
        $strBranchUrl = str_replace("{/branch}", "/" . $strBranchName, $githubPayload["repository"]["branches_url"]);

        foreach ($githubPayload["commits"] as $commit) {
            $arrCommits[] = "`<{$commit["url"]}|" . substr($commit["id"], 0, 8) . ">` - {$commit["message"]}";
        }

        $arrCommitSender = [
            [
                "type"      => "image",
                "image_url" => $githubPayload["sender"]["avatar_url"],
                "alt_text"  => $githubPayload["sender"]["login"],
            ],
            [
                "type" => "mrkdwn",
                "text" => "*<{$githubPayload["sender"]["html_url"]}|{$githubPayload["sender"]["login"]}>*",
            ],
        ];

        if (isset(config("slack.github.usernames")[$githubPayload["sender"]["login"]])) {
            $strSlackUsername = config("slack.github.usernames")[$githubPayload["sender"]["login"]];
            $arrCommitSender[] = [
                "type" => "mrkdwn",
                "text" => "<@{$strSlackUsername}>",
            ];
        }

        return [
            "color"  => "#4f4f4f",
            "blocks" => [
                [
                    "type"     => "context",
                    "elements" => $arrCommitSender,
                ],
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => "*<{$githubPayload["repository"]["html_url"]}|{$githubPayload["repository"]["full_name"]}>*",
                    ],
                ],
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => count($githubPayload["commits"]) . " new " . Str::plural('commit', count($githubPayload["commits"])) . " pushed to `<{$strBranchUrl}|$strBranchName>`",
                    ],
                ],
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => implode("\n", $arrCommits),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param LogError $logError
     * @return string
     * @throws \Exception
     */
    public function exceptionNotification(LogError $logError): string {
        $fields = [
            [
                "type" => "mrkdwn",
                "text" => "*Code:*\n" . $logError->exception_code,
            ],
            [
                "type" => "mrkdwn",
                "text" => "*When:*\n" . $logError->stamp_created_at,
            ],
        ];

        if (!is_null($logError->log_url)) {
            $fields[] = [
                "type" => "mrkdwn",
                "text" => "*Endpoint:*\n" . $logError->log_url,
            ];
            $fields[] = [
                "type" => "mrkdwn",
                "text" => "*Method:*\n" . $logError->log_method,
            ];
        } else if (!is_null($logError->log_command)) {
            $fields[] = [
                "type" => "mrkdwn",
                "text" => "*Command:*\n" . $logError->log_command,
            ];
        }

        if (!is_null($logError->user_uuid)) {
            $fields[] = [
                "type" => "mrkdwn",
                "text" => "*User UUID:*\n" . $logError->user_uuid,
            ];
        }

        if (!is_null($logError->log_instance)) {
            $fields[] = [
                "type" => "mrkdwn",
                "text" => "*Instance ID:*\n" . $logError->log_instance,
            ];
        }

        $response = $this->http->post(self::POST_MESSAGE_URI, [
            "json" => [
                "blocks" => [
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "New exception has been handled! UUID: " . $logError->row_uuid,
                        ],
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "*Message:*\n" . $logError->exception_message,
                        ],
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "*Exception Class:*\n" . $logError->exception_class,
                        ],
                    ],
                    [
                        "type"   => "section",
                        "fields" => $fields,
                    ],
                ],
            ],
        ]);

        $arrResponse = json_decode($response->getBody()->getContents(), true);

        if (!$arrResponse["ok"]) {
            throw new \Exception($arrResponse["error"] ?? "Something Went Wrong.", 400);
        }

        $logError->flag_slack_notified = true;
        $logError->save();

        return $arrResponse["ts"];
    }

    public function githubActionNotification(string $channel, array $githubPayload): string {
        try {
            $response = $this->http->post(self::POST_MESSAGE_URI, [
                "json" => [
                    "channel"     => $channel,
                    "text"        => self::GITHUB_ACTION_STATUS[$githubPayload["status"]]["message"],
                    "attachments" => [$this->buildActionMessageBody($githubPayload)],
                ],
            ]);
        } catch (\Exception $exception) {
            throw new SlackException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $arrResponse = json_decode($response->getBody()->getContents(), true);

        if (!$arrResponse["ok"]) {
            throw new SlackException($arrResponse["error"] ?? "Something Went Wrong.", 400);
        }

        return $arrResponse["ts"];
    }

    private function buildActionMessageBody(array $payload): array {
        if (!isset($payload["status"]) && !self::GITHUB_ACTION_STATUS[$payload["status"]]) {
            throw new BadRequestHttpException("Invalid Action Status.");
        }

        return [
            "color"  => self::GITHUB_ACTION_STATUS[$payload["status"]]["color"],
            "blocks" => [
                [
                    "type"   => "section",
                    "fields" => [
                        [
                            "type" => "mrkdwn",
                            "text" => "*Repository: <{$payload["repo_url"]}|{$payload["repo"]}>*",
                        ],
                        [
                            "type" => "mrkdwn",
                            "text" => "*Commit: `<" . $payload["repo_url"] . sprintf(self::GITHUB_COMMIT_URI, $payload["commit"]) . "|" . substr($payload["commit"], 0, 8) . ">`*",
                        ],
                        [
                            "type" => "mrkdwn",
                            "text" => "*Workflow: <" . $payload["repo_url"] . sprintf(self::GITHUB_ACTION_URI, $payload["commit"]) . "|{$payload["workflow"]}>*",
                        ],
                        [
                            "type" => "mrkdwn",
                            "text" => "*Initialized By: <" . sprintf(self::GITHUB_PROFILE_URL, $payload["actor"]) . "|{$payload["actor"]}>*",
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $message
     * @param string $host
     * @param string $channel
     * @throws SlackException
     */
    public function qldbNotification(string $message, string $host, string $channel): void {
        try {
            $this->http->post(self::POST_MESSAGE_URI, [
                "json" => [
                    "channel"     => $channel,
                    "text"        => "Ledger Exception",
                    "attachments" => [
                        [
                            "color"  => "#9e2b2b",
                            "blocks" => [
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "Message: {$message}",
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "Host: {$host}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $exception) {
            throw new SlackException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
    public function passportNotification(\Laravel\Passport\Client $client, array $ebInfo, string $errorMessage) {
        try {
            $response = $this->http->post(self::POST_MESSAGE_URI, [
                "json" => [
                    "channel"     => "notify-urgent-auth",
                    "text"        => null,
                    "attachments" => [
                        [
                            "color"  => "#9e2b2b",
                            "blocks" => [
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "@devbackend Automatically Update Of Passport ENV Variables Have Been Failed.",
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "EB ENV: {$ebInfo["name"]}",
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "Error: {$errorMessage}",
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "Passport Client ID: {$client->id}",
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "Passport Client Secret: {$client->secret}",
                                    ],
                                ],
                            ],
                        ]
                    ],
                ],
            ]);
        } catch (\Exception $exception) {
            throw new SlackException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $arrResponse = json_decode($response->getBody()->getContents(), true);

        if (!$arrResponse["ok"]) {
            throw new SlackException($arrResponse["error"] ?? "Something Went Wrong.", 400);
        }

        return $arrResponse["ts"];
    }

    public function supervisorNotification(string $queue, string $channel): void {
        try {
            $this->http->post(self::POST_MESSAGE_URI, [
                "json" => [
                    "channel"     => $channel,
                    "text"        => "Supervisor Shutdown",
                    "attachments" => [
                        [
                            "color"  => "#9e2b2b",
                            "blocks" => [
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "Queue: {$queue}",
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "ENV: " . ucfirst(env("APP_ENV")),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $exception) {
            throw new SlackException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function jobNotification(string $message, string $channel, string $jobType, string $jobName, ?User $objUser = null): void {
        try {
            $strUserName = $objUser ? $objUser->name : "systemJob";
            $strUserUuid = $objUser ? $objUser->user_uuid : "systemJob";

            $this->http->post(self::POST_MESSAGE_URI, [
                "json" => [
                    "channel"     => $channel,
                    "text"        => "Job Failed",
                    "attachments" => [
                        [
                            "color"  => "#9e2b2b",
                            "blocks" => [
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "Job Type: {$jobType}",
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "Job Name: {$jobName}",
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "Message: {$message}",
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "ENV: " . ucfirst(env("APP_ENV")),
                                    ],
                                ],
                                [
                                    "type" => "section",
                                    "text" => [
                                        "type" => "mrkdwn",
                                        "text" => "User: " . $strUserName . ". Uuid: " . $strUserUuid,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $exception) {
            throw new SlackException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function reportPlatformNotification(string $message, string $channel, string $strJobFileName, string $strReportFileName = null): void {
        $arrBlocks = [
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "ENV: " . ucfirst(env("APP_ENV")),
                ],
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "Message: {$message}",
                ],
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "Job File Name: {$strJobFileName}",
                ],
            ]
        ];

        if (!empty($strReportFileName)) {
            $arrBlocks[] = [
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => "Report File Name: {$strReportFileName}",
                    ],
                ]
            ];
        }

        try {
            $this->http->post(self::POST_MESSAGE_URI, [
                "json" => [
                    "channel"     => $channel,
                    "text"        => "Job Failed",
                    "attachments" => [
                        [
                            "color"  => "#9e2b2b",
                            "blocks" => $arrBlocks,
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $exception) {
            throw new SlackException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function reportProcessedFileNotification(array $arrProcessedFile, string $channel): void {
        $arrBlocks = [];

        foreach ($arrProcessedFile as $key => $value) {
            $arrBlocks[] = [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => $key . ": " . $value,
                ]
            ];
        }

        $arrBlocks[] = [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => "Environment: " . ucfirst(env("APP_ENV")),
            ],
        ];

        try {
            $this->http->post(self::POST_MESSAGE_URI, [
                "json" => [
                    "channel"     => $channel,
                    "text"        => "Report File Processed",
                    "attachments" => [
                        [
                            "color"  => "#9e2b2b",
                            "blocks" => $arrBlocks,
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $exception) {
            throw new SlackException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function reportExchangeRates(string $message): void {
        $this->http->post(self::POST_MESSAGE_URI, [
            "json" => [
                "channel"     => config("slack.channels.exceptions"),
                "text"        => "Get Exchange Rates Command Failed",
                "attachments" => [
                    [
                        "color"  => "#9e2b2b",
                        "blocks" => [
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Message: {$message}",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function reportAllExchangeRates(string $message): void {
        $this->http->post(self::POST_MESSAGE_URI, [
            "json" => [
                "channel"     => config("slack.channels.exceptions"),
                "text"        => "Get All Exchange Rates Command Failed",
                "attachments" => [
                    [
                        "color"  => "#9e2b2b",
                        "blocks" => [
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Message: {$message}",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "ENV: " . ucfirst(env("APP_ENV")),
                                ],
                            ]
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function reportMoveFileFails(ProjectModel $objProject): void {
        $this->http->post(self::POST_MESSAGE_URI, [
            "json" => [
                "channel"     => config("slack.channels.exceptions"),
                "text"        => "Soundblock Uploading Files",
                "attachments" => [
                    [
                        "color"  => "#9e2b2b",
                        "blocks" => [
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Message: File moving fails.",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Project: {$objProject->project_title}. Uuid: {$objProject->project_uuid};",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Account: {$objProject->account->account_name}. Uuid: {$objProject->account->account_uuid};",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "ENV: " . ucfirst(env("APP_ENV")),
                                ],
                            ]
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function chargeFailedReport(string $strChargeType, string $strAccountName, string $strAccountUuid, string $strPlanType){
        $this->http->post(self::POST_MESSAGE_URI, [
            "json" => [
                "channel"     => config("slack.channels.exceptions"),
                "text"        => "Failed Charge",
                "attachments" => [
                    [
                        "color"  => "#9e2b2b",
                        "blocks" => [
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Charge Type: {$strChargeType}.",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Account: {$strAccountName}. Uuid: {$strAccountUuid};",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Plan Type: {$strPlanType};",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "ENV: " . ucfirst(env("APP_ENV")),
                                ],
                            ]
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function chargeFailedExceptionReport(string $strChargeType, string $strAccountName, string $strAccountUuid, string $strPlanType, string $strMessage){
        $this->http->post(self::POST_MESSAGE_URI, [
            "json" => [
                "channel"     => config("slack.channels.exceptions"),
                "text"        => "Failed Charge",
                "attachments" => [
                    [
                        "color"  => "#9e2b2b",
                        "blocks" => [
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Charge Type: {$strChargeType}.",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Account: {$strAccountName}. Uuid: {$strAccountUuid};",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Plan Type: {$strPlanType};",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Message: {$strMessage};",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "ENV: " . ucfirst(env("APP_ENV")),
                                ],
                            ]
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function syncLedgerFailedReport(string $strMessage, string $strTable){
        $this->http->post(self::POST_MESSAGE_URI, [
            "json" => [
                "channel"     => config("slack.channels.exceptions"),
                "text"        => "Sync Ledger Cron Job Failed",
                "attachments" => [
                    [
                        "color"  => "#9e2b2b",
                        "blocks" => [
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Table: {$strTable}.",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "Message: {$strMessage}.",
                                ],
                            ],
                            [
                                "type" => "section",
                                "text" => [
                                    "type" => "mrkdwn",
                                    "text" => "ENV: " . ucfirst(env("APP_ENV")),
                                ],
                            ]
                        ],
                    ],
                ],
            ],
        ]);
    }
}
