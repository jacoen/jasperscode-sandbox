<?php

namespace App\Services;

use MailchimpMarketing\ApiClient;

class NewsletterService
{
    public function __construct(protected ApiClient $client)
    {

    }

    public function subscribe(string $email, string $list = null)
    {
        // if $list set the $list to the subscribers list in the services config
        $list ??= config('services.mailchimp.lists.subscribers');

        return $this->client->lists->addListMember($list, [
            'email_address' => $email,
            'status' => 'subscribed',
        ]);
    }
}
