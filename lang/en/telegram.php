<?php

return [
    'text' => [
        'lang' => "Выберите язык",
        'rules' => "Правила. Согласны?",
        'spam' => "Не стоит отправлять так много сообщений. Возьму перерыв...",
        'home' => "
<b>Token Scanner</b>
<code>Check a token for signs of fraud and get general information about the token. Useful for DYOR (Do Your Own Research).</code>

<b>Wallet Tracker</b>
<code>Track asset movements in any wallet in real-time.</code>

<b>Blackbox</b>
<code>Send anonymous reports about scammers and fraud activities to the RUGP project.</code>

<b>Check My Wallet</b>
<code>Check your wallet for suspicious and scam assets.</code>

<b>Academy</b>
<code>Free educational materials.</code>

<b>GPT</b>
<code>Utilize GPT capabilities for free.</code>

<b>My Profile</b>
<code>Customize your profile, change the interface language, and manage subscriptions.</code>
",
        'token_scanner' => [
            'main' => "Введи",
            'pending' => "Отчет будет отправлен вам сообщением, как только будет готов",
            'report' => [
                'text' => "
<b>:name | $:symbol</b>

:description

<i>Кол-во: </i><b>:supply</b>
<i>Холдеры: </i><b>:holders_count</b>

:pools
:links

:is_known_master
:is_known_wallet
:is_revoked
",
                'pool' => "<i><b><a href=':link'>:name</a></b></i>:
<i>Цена</i>: <b>$:price</b>
:lp_burned
:lp_locked
:tax_buy
:tax_sell
",
                'link' => "<a href=':url'><b>:Label</b></a> ",
                'is_known_master' => [
                    'yes' => "Этот контракт содержит проверенный код",
                    'no' => "Этот контракт содержит кастомный код",
                ],
                'is_known_wallet' => [
                    'yes' => "Контракт кошелька содержит проверенный код",
                    'no' => "Контракт кошелька содержит кастомный код",
                ],
                'is_revoked' => [
                    'yes' => "Права отозваны",
                    'no' => "Права не отозваны",
                ],
                'lp_burned' => [
                    'yes' => "<i>LP сожжены</i>: <b>:value%</b>",
                    'no' => "<i>LP сожжены</i>: <b>0%</b>",
                ],
                'lp_locked' => [
                    'yes' => "<i>LP заблокированы</i>: <b>:value% on :type :unlocks <i>:dyor</i></b>",
                    'no' => "<i>LP заблокированы</i>: <b>0%</b>",
                ],
                'tax_buy' => [
                    'yes' => "<i>Налог на покупку</i>: <b>:value%</b>",
                    'no' => "<i>Невозможно купить</i>",
                    'unknown' => "<i>Не удалось произвести проверку</i>",
                ],
                'tax_sell' => [
                    'yes' => "<i>Налог на продажу</i>: <b>:value%</b>",
                    'no' => "<i>Невозможно продать</i>",
                    'unknown' => "<i>Не удалось произвести проверку</i>",
                ],
            ],
            'chart' => [
                'text' => "
<b>:name | $:symbol</b>

<b>Чарт</b>

:pools
",
                'pool' => "<a href=':link'>:name</a>
<i>Цена:</i> <b>$:price</b>
<i>FDV:</i> <b>$:fdv</b>
<i>Ликвидность:</i> <b>$:reserve</b>
<i>Изменение цены</i> <i>(5м):</i> <b>:price_change_m5%</b> <i>(1ч):</i> <b>:price_change_h1%</b> <i>(6ч):</i> <b>:price_change_h6%</b> <i>(24ч):</i> <b>:price_change_h24%</b>
<i>Пул создан:</i> <b>:created_at</b>
",
            ],
            'holders' => [
                'text' => "
<b>:name | $:symbol</b>

<b>Холдеры</b>

:holders
",
                'holder' => "<a href='tonviewer.com/:address'><i>:label</i></a>: <b>:balance (:percent%)</b>\n",
                'dex_lock_stake' => "DEX/LOCK/STAKE?",
            ],
            'volume' => [
                'text' => "
<b>:name | $:symbol</b>

<b>Объем</b>

:pools
",
                'pool' => "<a href=':link'>:name</a>
<i>Цена:</i> <b>$:price</b>
<i>Объем</i> <i>(5м): </i> <b>$:volume_m5</b> <i>(1ч): </i> <b>$:volume_h1</b> <i>(6ч): </i> <b>$:volume_h6</b> <i>(24ч): </i> <b>$:volume_h24</b>
<i>Покупки</i> <i>(5м): </i> <b>:buys_m5</b> <i>(1ч): </i> <b>:buys_h1</b> <i>(6ч): </i> <b>:buys_h6</b> <i>(24ч): </i> <b>:buys_h24</b>
<i>Продажи</i> <i>(5м): </i> <b>:sells_m5</b> <i>(1ч): </i> <b>:sells_h1</b> <i>(6ч): </i> <b>:sells_h6</b> <i>(24ч): </i> <b>:sells_h24</b>
<i>Пул создан:</i> <b>:created_at</b>
",
            ],
        ],
    ],
    'errors' => [
        'address' => [
            'invalid' => "Неверный CA",
            'empty' => "По данному CA ничего не найдено",
        ],
        'scan' => [
            'metadata' => "Невозможно получить информацию по токену :address. Попробуйте позже",
            'simulator' => "Невозможно произвести проверки по токену :address. Попробуйте позже",
        ],
    ],
    'buttons' => [
        'ru' => "🇷🇺 Русский",
        'en' => "🇺🇸 English",

        'back' => "Назад",
        'agree' => "Согласен",

        'token_scanner' => "🔎 Token Scanner",
        'wallet_tracker' => "👀 Wallet Tracker",
        'black_box' => "🗃 Black Box",
        'check_wallet' => "🚨 Check My Wallet",
        'academy' => "📚 The Academy",
        'gpt' => "💡 GPTo",
        'profile' => "⚙️ Profile",

        'report' => "Main", // Главная в отчете
        'chart' => "Chart",
        'holders' => "Holders",
        'volume' => "Volume",
    ],
];
