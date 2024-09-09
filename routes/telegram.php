<?php

use App\Telegram\Conversations\HomeMenu;
use App\Telegram\Handlers\CommandsHandler;
use App\Telegram\Handlers\TokenReportHandler;
use App\Telegram\Handlers\UsersHandler;
use App\Telegram\Middleware\ForDevelopers;
use App\Telegram\Middleware\ForSuperusers;
use App\Telegram\Middleware\PrivateHandler;
use App\Telegram\Middleware\RetrieveAccount;
use SergiX44\Nutgram\Nutgram;

/** @var Nutgram $bot */
$bot->middleware(RetrieveAccount::class);

$bot->group(function (Nutgram $bot) {

    $bot->onCallbackQueryData('reports:token:{token}:{type}', [TokenReportHandler::class, 'route']);
    $bot->onCommand('start {params}', HomeMenu::class);
    $bot->onCommand('start', HomeMenu::class);

    $bot->onCommand('commands', CommandsHandler::class)->middleware(ForDevelopers::class);
    $bot->onCommand('users', UsersHandler::class)->middleware(ForSuperusers::class);

})->middleware(PrivateHandler::class);
