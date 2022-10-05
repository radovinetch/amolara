<?php

namespace App\Console\Commands;

use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TagModel;
use App\Models\Lead;
use App\Models\Tag;
use App\Models\Token;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use League\OAuth2\Client\Token\AccessToken;

class LeadsUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:upload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload lead from amoCRM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $apiClient = new \AmoCRM\Client\AmoCRMApiClient(
            env('AMOCRM_CLIENT_ID'),
            env('AMOCRM_CLIENT_SECRET'),
            env('AMOCRM_REDIRECT_URI')
        );

        $guzzleHttp = new Client();

        $accessTokenModel = Token::first();
        if ($accessTokenModel === null) {
            try {
                $accessTokenArray = json_decode($guzzleHttp->request('POST', 'https://nikitaaltus.amocrm.ru/oauth2/access_token/', [
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'client_id' => env('AMOCRM_CLIENT_ID'),
                        'client_secret' => env('AMOCRM_CLIENT_SECRET'),
                        'code' => env('AMOCRM_AUTH_TOKEN'),
                        'redirect_uri' => env('AMOCRM_REDIRECT_URI')
                    ]
                ])->getBody()->getContents(), true);
                $accessTokenModel = Token::create([
                    'token_type' => $accessTokenArray['token_type'],
                    'expires_in' => $accessTokenArray['expires_in'],
                    'access_token' => $accessTokenArray['access_token'],
                    'refresh_token' => $accessTokenArray['refresh_token'],
                ]);
            } catch (ClientException $exception) {
                echo 'Ошибка обмена auth token на acces_token, возможно auth token устарел';
                return Command::FAILURE;
            }
        }

        $apiClient
            ->setAccessToken(new AccessToken($accessTokenModel->toArray()))
            ->setAccountBaseDomain(env('AMOCRM_BASE_DOMAIN'))
            ->onAccessTokenRefresh(
                function (\League\OAuth2\Client\Token\AccessTokenInterface $accessToken, string $baseDomain) {
                    DB::table('tokens')->delete();
                    Token::create([
                        'token_type' => 'Bearer',
                        'expires_in' => $accessToken->getExpires(),
                        'access_token' => $accessToken->getToken(),
                        'refresh_token' => $accessToken->getRefreshToken(),
                    ]);
                }
            );

        /**
         * @var LeadModel[] $leads
         */
        $leads = $apiClient->leads()->get()->all();

        $count = 0;

        foreach ($leads as $lead) {
            $user = $apiClient->users()->getOne($lead->getResponsibleUserId());
            $status = $apiClient->statuses($lead->getPipelineId())->getOne($lead->getStatusId());
            $company = $lead->getCompany();

            if ($company !== null) {
                DB::table('companies')->insertOrIgnore([
                    'id' => $company->getId(),
                    'name' => $company->getName()
                ]);
            }

            /** @var TagModel[] $tags */
            $tags = ($t = $lead->getTags()) === null ? [] : $t->all();

            DB::table('users')->insertOrIgnore([
                'id' => $user->getId(),
                'uuid' => $user->getUuid(),
                'name' => $user->getName(),
                'email' => $user->getEmail()
            ]);

            DB::table('statuses')->insertOrIgnore([
                'id' => $status->getId(),
                'name' => $status->getName()
            ]);

            foreach ($tags as $tag) {
                DB::table('tags')->insertOrIgnore([
                    'leadId' => $lead->getId(),
                    'id' => $tag->getId(),
                    'name' => $tag->getName()
                ]);
            }

            DB::table('leads')->insertOrIgnore([
                'id' => $lead->getId(),
                'name' => $lead->getName(),
                'responsibleUserId' => $lead->getResponsibleUserId(),
                'groupId' => $lead->getGroupId(),
                'createdBy' => $lead->getCreatedBy(),
                'updatedBy' => $lead->getCreatedBy(),
                'createdAt' => $lead->getCreatedAt(),
                'updatedAt' => $lead->getUpdatedAt(),
                'accountId' => $lead->getAccountId(),
                'pipelineId' => $lead->getPipelineId(),
                'statusId' => $lead->getStatusId(),
                'closedAt' => $lead->getClosedAt(),
                'closestTaskAt' => $lead->getClosestTaskAt(),
                'price' => $lead->getPrice(),
                'lossReasonId' => $lead->getLossReasonId(),
                'lossReason' => ($lossReason = $lead->getLossReason()) === null ? null : $lossReason->getName(),
                'isDeleted' => $lead->getIsDeleted(),
                'companyId' => $company?->getId(),
                'isMerged' => $lead->isMerged()
            ]);
            $count++;
        }

        echo "Данные из amoCRM успешно подгружены, таблицы обновлены" . PHP_EOL . "Всего обработано " . $count . " лидов";
        return Command::SUCCESS;
    }
}
