<?php

return [
    'text' => [
        'lang' => "👇 Please choose your language.",
        'rules' => "<b>TERMS OF USE:</b>
By using this Telegram bot, you confirm and agree that the use of the bot is at your own risk.
The creators of this bot are not responsible for any losses or damages that may arise from its use.
The bot is provided as is, without any warranties, express or implied.
The user is responsible for verifying the accuracy of the information provided and taking necessary precautions to protect themselves from potential fraudulent actions and other risks.",
        'spam' => "🏖 Do not Spam please. I'll take a break...",
        'home' => "

<b>RUGP bot - your support in crypto!</b>

Tools:

<b>🔎 Token Scanner</b> - <code>Scam check + Complete information about the token. Tool for DYOR.</code>
BETA 0.1

<b>👀 Wallet Tracker</b> - <code>Realtime wallets tracking.</code>
🔜

<b>🗃 Blackbox</b> - <code>To send insights about fraudulent schemes or scammers anonymously to RUGP.</code>
🔜

<b>🚨 Check My Wallet</b> - <code>Check your wallet for scams and vulnerabilities.</code>
🔜

<b>📚 Academy</b> - <code>Free educational stuff.</code>
🔜

<b>💡 GPT</b> - <code>Free GPT to ask about crypto.</code>
🔜



   <a href='http://rugp.io'>WEB</a>    |    <a href='t.me/rugp_ton'>Telegram chat</a>    |     <a href='https://x.com/rugp_ton'>Twitter</a>

© RUGP - anti-scam community and utilities on TON.
Please report bot bugs in tg chat. 🙏

",
        'token_scanner' => [
            'main' => "👇 Enter a token, pool address or dex link.",
            'pending' => "🔎 Scanning. The report will be sent to you shortly.",
            'report' => [
                'text' => "



<b>ℹ️ INFO</b>

<b>:name | $:symbol</b>
<b>CA:</b> <code>:address</code>

📃 <u><b>Description:</b></u>
<i>:description</i>

:is_known_master

:is_revoked:is_revoked_warning

<b>🔢 Supply:</b> :supply
<b>👨‍👦‍👦 Holders:</b> :holders_count

🔄 <u><b>DEX's:</b> </u>
:pools
:rugpull_warning
:lp_burned_warning:has_links:links
<u><b>Community trust:</b></u>
👍 <b>:likes_count</b> / <b>:dislikes_count</b> 👎

Click 🔎 for new scan.

",
                'pool' => "
<a href=':link'><i><b>:name</b></i></a>:
├💵 Price: <b>$:price</b>
:tax_buy
:tax_sell
:lp_burned:lp_locked
",
                'link' => "<a href=':url'><b>:Label</b></a> ",
                'has_links' => "\n<u><b>Соцсети:</b></u>\n",
                'rugpull' => "<b>WARNING ⁉️RUGPULL⁉️</b>\n",
                'is_known_master' => [
                    'yes' => "✅ VERIFIED MASTER",
                    'no' => "⚠️ NON-STANDARD MASTER",
                ],
                'is_known_wallet' => [
                    'yes' => "✅ Проверенный код у кошелька",
                    'no' => "⚠️ Кастомный код у кошелька",
                ],
                'is_revoked' => [
                    'yes' => "✅ REWOKE: YES",
                    'no' => "⚠️ REWOKE: NO</b>",
                ],
                'is_revoked_warning' => [
                    'yes' => "Owner can't change supply, change tax or make honeypot",
                    'no' => "Owner can change supply, change tax or make honeypot.
Buy only if you trust the project.",
                ],
                'lp_burned' => [
                    'yes' => "├✅  <i>LP burned</i>: <b>:value%</b>",
                    'no' => "├⚠️ <i>LP not burned</i>  ",
                    'warning' => "⚠️ Liquidity (LP) not burned or locked.
- If you trust the project, then it doesn't matter.
- DYOR --> ♻️.

"
                ],
                'lp_locked' => [
                    'yes' => "\n└🔒 <i>LP locked</i>
      <b>:value% <a href=':link'>:type</a> :unlocks</b>",
                    'no' => "\n└🔒 <i>LP not locked</i>",
                    'burned' => "",
                    'unlocks' => "(till :value)",
                    'dyor' => "/ more locks! DYOR",
                ],
                'tax_buy' => [
                    'unknown' => "├<i>🤷‍♂️ Failed to check jetton</i>",
                    'no' => "├<i>🤦🏻 Can't buy jetton</i>",
                    'ok' => "├<i>✅ Buy tax</i>: <b>no</b>",
                    'warning' => "├<i>⚠️ Buy tax</i>: <b>:value%</b>",
                    'danger' => "├<i>🚨 % Buy tax</i>: <b>:value%</b>",
                ],
                'tax_sell' => [
                    'unknown' => "└<i>🤷‍♂️ Failed to check jetton</i>",
                    'no' => "
<b>CAN'T SELL JETTON</b>

‼️HONEYPOT‼️SCAM‼️",
                    'ok' => "├<i>✅ Sell tax</i>: <b>нет</b>",
                    'warning' => "├<i>⚠️ Sell tax</i>: <b>:value%</b>",
                    'danger' => "├<i>❌ Sell tax</i>: <b>:value%</b>",
                ],

            ],
            'chart' => [
                'text' => "

📈 <b>PRICE</b> <b>$:symbol</b>

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
<b>$:symbol</b>

👨‍👦‍👦 <b>TOP 10 HOLDERS</b>

:holders
:pools:warning


",
                'pool' => "💧 <u>:name</u> <b>pool liquidity</b> \n\n:holders\n",
                'holder' => "<b>:percent%</b> -> <a href='tonviewer.com/:address'><i>:label</i></a>\n",
                'dex_lock_stake' => "DEX/LOCK/STAKE?",
                'warning' => "🔥 zero-address - burning address.
🔒 DEX/LOCK/STAKE - tokens were sent to dex, locking, staking, etc. DYOR.
🔒 tinu-locker - locker address.
⚠️ MEXC, Bybit or OKX in holders in new weak coin = SCAM coin!"
            ],
            'volume' => [
                'text' => "

📊 <b>ОБЪЕМ</b> <b>$:symbol</b>

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

:warning

",
                'warning' => "⚠️ Check buys/sells ratio! Too many buys and single sales - it could be a SCAM!",
            ],
        ],
        'profile' => [
            'main' => "
<b>Language</b>: <i>:language</i>
<b>Warnings</b>: <i>:is_hide_warnings</i>
",
            'warnings' => [
                'hidden' => 'Hidden',
                'shown' => 'Visible',
            ],
        ],
    ],
    'errors' => [
        'address' => [
            'invalid' => "🤷‍♂️ Wrong address",
            'empty' => "🤷‍♂️ Nothing found.
Possible reasons: invalid address, deleted scam or no purchases and/or sales of a token for a long time.",
        ],
        'scan' => [
            'metadata' => "🚧 Can't scan :address. Please try later",
            'simulator' => "🚧 Can't scan :address. Please try later",
        ]
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
        'holders' => "♻️",
        'volume' => "📊",
        'like' => "👍",
        'dislike' => "👎",
        'to_scanner' => "🔎",
        'to_home' => "🏠",

        'warnings_hidden' => "⚠️ show",
        'warnings_shown' => "⚠️ hide",
    ],
];
