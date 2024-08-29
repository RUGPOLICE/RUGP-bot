<?php

use App\Telegram\Conversations\Home;
use App\Telegram\Handlers\TokenReportHandler;
use App\Telegram\Middleware\RetrieveAccount;
use Nutgram\Laravel\Facades\Telegram;

Telegram::middleware(RetrieveAccount::class);
Telegram::onCallbackQueryData('reports:token:{token}:{type}', [TokenReportHandler::class, 'route']);

Telegram::onCommand('start {params}', Home::class);
Telegram::onCommand('start', Home::class);

