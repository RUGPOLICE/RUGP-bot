<?php

return [
    'text' => [
        'lang' => "👇 Please choose your language.",
        'rules' => "<b>TERMS OF USE:</b>
By using this Telegram bot, you confirm and agree that the use of the bot is at your own risk. The creators of this bot are not responsible for any losses or damages that may arise from its use. The bot is provided as is, without any warranties, express or implied. The user is responsible for verifying the accuracy of the information provided and taking necessary precautions to protect themselves from potential fraudulent actions and other risks.",
        'spam' => "🏖 It's not worth sending so many messages. I'll take a break...",
        'home' => "

<b>🔎 Token Scanner</b> - <code>Scam check. Complete information about the token. Tool for DYOR.</code>

<b>👀 Wallet Tracker</b> - <code>Realtime wallets tracking.</code>

<b>🗃 Blackbox</b> - <code>To send insights about fraudulent schemes or scammers anonymously to RUGP.</code>

<b>🚨 Check My Wallet</b> - <code>Check your wallet for scams and vulnerabilities.</code>

<b>📚 Academy</b> - <code>Free educational stuff.</code>

<b>💡 GPT</b> - <code>Free GPT.</code>


   <a href='http://rugo.io'>WEB</a>    |    <a href='t.me/rugp_ton'>Telegram</a>    |     <a href='https://x.com/rugp_ton'>Twitter</a>

",
        'token_scanner' => [
            'main' => "👇 Enter token or pool address.",
            'pending' => "🔎 Scanning. The report will be sent to you shortly.",
            'report' => [
                'text' => "



<b>ℹ️ INFO</b>

<b>:name | $:symbol</b>

<i>:description</i>

:is_known_master
:is_known_wallet
:is_revoked

<b>🔢 Supply:</b> :supply
<b>👨‍👦‍👦 Holders:</b> :holders_count

🔄 <u><b>DEX's:</b> </u>
:pools
:rugpull_warning:lp_burned_warning:has_links:links
<u><b>Community trust:</b></u>
👍 <b>:likes_count</b> / <b>:dislikes_count</b> 👎

Click 🔎 for new scan.

",
                'pool' => "
<i><b>:name</b></i>:
├💵 Цена: <b>$:price</b>
:lp_burned:lp_locked
:tax_buy
:tax_sell
",
                'link' => "<a href=':url'><b>:Label</b></a> ",
                'has_links' => "\n<u><b>Соцсети:</b></u>\n",
                'rugpull' => "RUGPULL\n",
                'is_known_master' => [
                    'yes' => "✅ VERIFIED MASTER",
                    'no' => "⚠️ NON-STANDARD MASTER",
                ],
                'is_known_wallet' => [
                    'yes' => "✅ VERIFIED WALLET",
                    'no' => "⚠️ NON-STANDARD WALLET",
                ],
                'is_revoked' => [
                    'yes' => "✅ REWOKE: YES",
                    'no' => "⚠️ REWOKE: NO",
                ],
                'lp_burned' => [
                    'yes' => "├✅  <i>LP burned</i>: <b>:value%</b>",
                    'no' => "├⚠️ <i>LP not burned</i>  ",
                    'warning' => "⚠️ Не вся LP сожжена или меньше 99% заблокировано.
- Если проект недавно запустился, такое может быть.
- Возможна не стандартная механика.
- Подробнее можно посмотреть в  👨‍👦‍👦 холдерах.
- DYOR
"
                ],
                'lp_locked' => [
                    'yes' => "\n├🔒 <i>LP locked</i>: <b>:value% :type :unlocks <i>:dyor</i></b>",
                    'no' => "\n├🔒 <i>LP locked</i>: <b>0%</b>",
                    'burned' => "",
                    'dyor' => "/ more locks! DYOR",
                ],
                'tax_buy' => [
                    'unknown' => "<i>🤷‍♂️ Failed to check jetton</i>",
                    'no' => "├<i>🤦🏻 Can't buy jetton</i>",
                    'ok' => "├<i>✅ Buy tax</i>: <b>No</b>",
                    'warning' => "├<i>⚠️ Buy tax</i>: <b>:value%</b>",
                    'danger' => "├<i>🚨 Buy tax</i>: <b>:value%</b>",
                ],
                'tax_sell' => [
                    'unknown' => "└<i>🤷‍♂️ Failed to check jetton</i>",
                    'no' => "
<b>CAN'T SELL JETTON</b>

‼️HONEYPOT‼️SCAM‼️",
                    'ok' => "└<i>✅ Sell tax</i>: <b>No</b>",
                    'warning' => "└<i>⚠️ Sell tax</i>: <b>:value%</b>",
                    'danger' => "└<i>❌ Sell tax</i>: <b>:value%</b>",
                ],
            ],
            'chart' => [
                'text' => "

📈 <b>CHART</b> <b>$:symbol</b>

:pools
",
                'pool' => "
<a href=':link'>:name</a>
💵 <i>Price:</i> <b>$:price</b>
🏦 <i>FDV:</i> <b>$:fdv</b>
💦 <i>Liquidity:</i> <b>$:reserve</b>
📉 <u><i>Price change:</i></u>
├<i>(5m):</i> <b>:price_change_m5%</b>
├<i>(1h):</i> <b>:price_change_h1%</b>
├<i>(6h):</i> <b>:price_change_h6%</b>
└<i>(24h):</i> <b>:price_change_h24%</b>

<i>Pool created:</i> <b>:created_at</b>
",
            ],
            'holders' => [
                'text' => "
👨‍👦‍👦 <b>HOLDERS</b> <b>$:symbol</b>

:holders

",
                'holder' => "<a href='tonviewer.com/:address'><i>:label</i></a>: <b>:balance (:percent%)</b>\n",
                'dex_lock_stake' => "DEX/LOCK/STAKE?",
            ],
            'volume' => [
                'text' => "

📊 <b>VOLUME</b> <b>$:symbol</b>

:pools

",
                'pool' => "<a href=':link'>:name</a>
🔈 <u><i>Vol</i></u>
├<i>(5m): </i> <b>$:volume_m5</b>
├<i>(1h): </i> <b>$:volume_h1</b>
├<i>(6h): </i> <b>$:volume_h6</b>
└<i>(24h): </i> <b>$:volume_h24</b>
🔼 <u><i>Bought</i></u>
├<i>(5m): </i> <b>:buys_m5</b>
├<i>(1h): </i> <b>:buys_h1</b>
├<i>(6h): </i> <b>:buys_h6</b>
└<i>(24h): </i> <b>:buys_h24</b>
🔽 <u><i>Sold</i></u>
├<i>(5m): </i> <b>:sells_m5</b>
├<i>(1h): </i> <b>:sells_h1</b>
├<i>(6h): </i> <b>:sells_h6</b>
└<i>(24h): </i> <b>:sells_h24</b>

",
            ],
        ],
        'profile' => [
            'main' => "
<b>Language</b>: <i>:language</i>
",
        ],
    ],
    'errors' => [
        'address' => [
            'invalid' => "Wrong adress",
            'empty' => "Nohing found",
        ],
        'scan' => [
            'metadata' => "Can't scan :address. Please try later",
            'simulator' => "Can't scan :address. Please try later",
        ],
    ],
    'buttons' => [
        'ru' => "🇷🇺 RUS",
        'en' => "🇺🇸 ENG",

        'back' => "Back",
        'cancel' => "Cancel",
        'agree' => "🤝 Agreed",

        'token_scanner' => "🔎 Token Scanner",
        'wallet_tracker' => "👀 Wallet Tracker",
        'black_box' => "🗃 Black Box",
        'check_wallet' => "🚨 Check My Wallet",
        'academy' => "📚 Academy",
        'gpt' => "💡 GPTo",
        'profile' => "⚙️ My profile",

        'report' => "ℹ️", // Главная в отчете
        'chart' => "📈",
        'holders' => "👨‍👦‍👦",
        'volume' => "📊",
        'like' => "👍",
        'dislike' => "👎",
        'to_scanner' => "🔎",
        'to_home' => "🏠",

        'simulation_on' => "Симуляция: вкл",
        'simulation_off' => "Симуляция: выкл",
    ],
];
