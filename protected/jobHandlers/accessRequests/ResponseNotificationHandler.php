<?php
declare(strict_types=1);

namespace prime\jobHandlers\accessRequests;

use JCIT\jobqueue\interfaces\JobInterface;
use prime\jobs\accessRequests\ResponseNotificationJob;
use prime\repositories\AccessRequestRepository;
use yii\mail\MailerInterface;

class ResponseNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private AccessRequestRepository $accessRequestRepository
    ) {
    }

    /**
     * @param ResponseNotificationJob $job
     */
    public function handle(JobInterface $job): void
    {
        $accessRequest = $this->accessRequestRepository->retrieveOrThrow($job->getAccessRequestId());

        $this->mailer->compose(
            'access_request_response_notification',
            [
                'continueRoute' => ['/'],
                'accessRequest' => $accessRequest,
            ]
        )
            ->setTo($accessRequest->createdByUser->email)
            ->send()
        ;
    }
}
