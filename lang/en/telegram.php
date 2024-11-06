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
BETA 0.2

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

   <a href='http://rugp.io'>WEB</a>   |    <a href='https://t.me/rugpolicenews'>Telegram chat</a>    |     <a href='https://x.com/rugp_ton'>Twitter</a>

© RUGP - anti-scam community and utilities on TON.
Please report bot bugs in tg chat. 🙏

",
        'token_scanner' => [
            'main' => "👇 Enter a token name with <b>$</b>, token address, pool address or dex link.\nNetwork: <b>:network</b>",
            'examples' => "\n\nExamples:\nSymbol: <code>\$rugp</code>\nToken address: <code>EQAXUTLNVMa_Hbm_GX2NzxvtoOA_iJU2d5Tf0E715MY_RUGP</code>\nPool address: <code>EQAarUZyr_WFG3sdV9wY6ft5a-Ugm1_bbHjNKrSC32UzI_As</code>\nLink: <code>https://www.geckoterminal.com/ton/pools/EQAarUZyr_WFG3sdV9wY6ft5a-Ugm1_bbHjNKrSC32UzI_As</code>",
            'pending' => "🔎 Scanning. The report will be sent to you shortly.",
            "watermark" => "
<a href='http://rugp.io'>WEB</a> | <a href='https://t.me/rugpolicenews'>Telegram</a> | <a href='https://x.com/rugp_ton'>Twitter</a>
© RUGP
",
            'report' => [
                'text' => "
<b>ℹ️ INFO</b>

<b>:name | $:symbol on :NETWORK</b>
<code>:address</code>\n:alert:description_title<i>:description</i>

:is_known_master
:is_known_wallet

:is_revoked:is_revoked_warning

<b>🔢 Supply:</b> :supply
<b>👨‍👦‍👦 Holders:</b> :holders_count
<b>🛢 Market Cap:</b> $:market_cap

🔄 <u><b>DEX's:</b></u>
:pools:alert:lp_burned_warning:links_title:links
<u><b>Community trust:</b></u>
👍 <b>:likes_count</b> / <b>:dislikes_count</b> 👎
:is_finished:watermark",
                'pool' => "
<a href=':link'><i><b>:name</b></i></a>:
├💵 Price: <b>$:price</b>
:tax_buy
:tax_sell
:lp_burned:lp_locked
",
                'link' => "<a href=':url'><b>:Label</b></a> ",
                'links_title' => "\n<u><b>Socials:</b></u>\n",
                'description_title' => "\n<u><b>Description:</b></u>\n",
                'is_finished' => "\nClick 🔎 for new scan.",
                'is_known_master' => [
                    'yes' => "✅ VERIFIED MASTER",
                    'no' => "⚠️ NON-STANDARD MASTER",
                    'scan' => "🔎 Scanning...",
                ],
                'is_known_wallet' => [
                    'yes' => "✅ VERIFIED JETTON",
                    'no' => "⚠️ NON-STANDARD JETTON",
                    'scan' => "🔎 Scanning...",
                ],
                'is_revoked' => [
                    'yes' => "✅ REVOKED.",
                    'no' => "⚠️ NOT REVOKED.",
                ],
                'is_revoked_warning' => [
                    'yes' => "\nOwner can't change supply, tax or make honeypot.",
                    'no' => "\nOwner can change supply, tax or make honeypot.\nBuy only if you trust the project.",
                ],
                'lp_burned' => [
                    'yes' => "├✅  <i>LP burned</i>: <b>:value%</b>",
                    'no' => "├⚠️ <i>LP not burned</i>",
                    'scan' => "├🔎 <i>Scanning...</i>",
                    'warning' => "
⚠️ Liquidity (LP) not burned or locked.
- If you trust the project, then it doesn't matter.
- DYOR -> ♻️.
"
                ],
                'lp_locked' => [
                    'yes' => "\n└🔒 <i>LP locked</i>\n<b>:value% on <a href=':link'>:type</a></b> :unlocks",
                    'no' => "\n└🔒 <i>LP not locked</i>",
                    'multiple' => "\n└🔒 <i>Multiple locks (:value%) -> ♻️</i>",
                    'scan' => "\n└🔎 <i>Scanning...</i>",
                    'burned' => "",
                    'unlocks' => "till :value",
                    'dyor' => "/ more locks! DYOR",
                ],
                'tax_buy' => [
                    'scan' => "├<i>🔎️ Scanning...</i>",
                    'unknown' => "├<i>🤷‍♂️ Failed to check jetton</i>",
                    'no' => "├<i>🤦🏻 Can't buy jetton</i>",
                    'ok' => "├<i>✅ Buy tax</i>: <b>no</b>",
                    'warning' => "├<i>⚠️ Buy tax</i>: <b>:value%</b>",
                    'danger' => "├<i>🚨 % Buy tax</i>: <b>:value%</b>",
                ],
                'tax_sell' => [
                    'scan' => "├<i>🔎️ Scanning...</i>",
                    'unknown' => "├<i>🤷‍♂️ Failed to check jetton</i>",
                    'no' => "└<i>🤦🏻 Can't sell jetton</i>",
                    'ok' => "├<i>✅ Sell tax</i>: <b>no</b>",
                    'warning' => "├<i>⚠️ Sell tax</i>: <b>:value%</b>",
                    'danger' => "├<i>❌ Sell tax</i>: <b>:value%</b>",
                ],
                'alerts' => [
                    'is_warn_honeypot' => "\n❌HONEYPOT❌SCAM❌\n",
                    'is_warn_rugpull' => "\n⁉️WARNING, Potential RUGPULL⁉️\n",
                    'is_warn_original' => "\n✅✅ORIGINAL COIN✅✅\n",
                    'is_warn_scam' => "\n‼️SCAM‼️\n",
                    'is_warn_liquidity' => "\n‼️Warning, low liquidity‼️\n",
                ],
            ],
            'chart' => [
                'text' => "

📈 <b>PRICE</b> <b>$:symbol</b>

:pools:warning:warnings
:watermark
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

<i>Pool created:</i> <b>:created_at</b>
",
                'warning' => "⚠️ Check buys/sells ratio! Too many buys and single sales could be a SCAM!",
                'warnings' => "\n⚙️ You can switch off warnings at any time in your profile.",
                'clock' => "Please, press the clock button first",
            ],
            'holders' => [
                'text' => "
<b>$:symbol</b>

👨‍👦‍👦 <b>TOP HOLDERS (:actual OF :total)</b>

:holders
:pools:warning
:watermark
",
                'pool' => "💧 <a href='https://tonviewer.com/:address?section=holders'><u>:name</u></a> <b>liquidity pool</b> \n\n:holders\n",
                'holder' => "<b>:percent%</b> -> <a href=':address'><i>:label</i></a>\n",
                'dex_lock_stake' => "DEX/LOCK/STAKE?",
                'warning' => "🔥 zero-address - burning address.
🔒 DEX/LOCK/STAKE - tokens were sent to dex, locking, staking, etc. DYOR.
🔒 tinu-locker - locker address.
⚠️ MEXC, Bybit or OKX as holders in new weak coin = SCAM coin!"
            ],
        ],
        'profile' => [
            'main' => "
<b>Language</b>: <i>:language</i>
",
            'language' => "Choose language",
        ],
        'settings' => [
            'main' => "
🔎 Token Scanner - Scam check + Complete information about the token.
BETA 0.2
<a href='http://rugp.io'>WEB</a> | <a href='https://t.me/rugpolicenews'>Telegram chat</a> | <a href='https://x.com/rugp_ton'>Twitter</a>


<u>SETTINGS</u>

Warnings: <b>:is_show_warnings</b>
Scam notifications: <b>:is_show_scam</b>
Language: <b>:language</b>
Network: <b>:network</b>
",
            'is_show_warnings' => [
                'yes' => "ON",
                'no' => "OFF",
            ],
            'is_show_scam' => [
                'yes' => "ON",
                'no' => "OFF",
            ],
            'blank_network' => "None",
        ],
        'scanner_settings' => [
            'main' => "
Warnings: <b>:is_show_warnings</b>
Scam notifications: <b>:is_show_scam</b>
Network: <b>:network</b>
",
            'is_show_warnings' => [
                'yes' => "ON",
                'no' => "OFF",
            ],
            'is_show_scam' => [
                'yes' => "ON",
                'no' => "OFF",
            ],
            'network' => "Choose network\n⚠️ All networks except TON are in test mode. Check for further updates.",
            'blank_network' => "None",
        ],
        'group' => "
🔎 Token Scanner - Scam check + Complete information about the token.
BETA 0.2
<a href='http://rugp.io'>WEB</a> | <a href='https://t.me/rugpolicenews'>Telegram chat</a> | <a href='https://x.com/rugp_ton'>Twitter</a>

Add bot to your group with admin rights.

Main bot - - > @rugpbot
",
        'gpt' => [
            'main' => "Enter prompt",
            'error' => "Error. Try again later.",
        ],
    ],
    'errors' => [
        'address' => [
            'invalid' => "🤷‍♂️ Wrong address",
            'symbol' => "🤷‍♂️ Jetton not found. Try to enter jetton address",
            'empty' => "🤷‍♂️ Nothing found.\nPossible reasons: invalid address, deleted scam or no purchases and/or sales of a token for a long time.",
            'retype' => "🤷‍♂️ Invalid query.\nPlease send ticker starts with $ or address or dex link.",
        ],
        'scan' => [
            'metadata' => "🚧 Can't scan :address. Please try later",
            'simulator' => "🚧 Can't scan :address. Please try later",
            'fail' => "🚧 Internal error while scanning :address. Please try later",
        ]
    ],
    'buttons' => [
        'ru' => "🇷🇺 RUS",
        'en' => "🇺🇸 ENG",

        'back' => "Back",
        'cancel' => "Cancel",
        'ok' => "OK",
        'agree' => "🤝 Agreed",

        'token_scanner' => "🔎 Token Scanner",
        'wallet_tracker' => "🔜 Wallet Tracker",
        'black_box' => "🔜 Black Box",
        'check_wallet' => "🔜 Check My Wallet",
        'academy' => "🔜 Academy",
        'gpt' => "🔜 GPTo",
        'profile' => "⚙️ My profile",

        'report' => "ℹ️", // Главная в отчете
        'chart' => "📈",
        'holders' => "♻️",
        'volume' => "📊",
        'like' => "👍",
        'dislike' => "👎",
        'clock' => "🕔",
        'chart_aggregate_1' => "1M",
        'chart_aggregate_2' => "15M",
        'chart_aggregate_3' => "4H",
        'chart_aggregate_4' => "1D",
        'to_scanner' => "🔎",
        'to_home' => "🏠",
        'to_settings' => "⚙️",
        'pro' => "⭐️",

        'warnings_hidden' => "⚠️ SHOW",
        'warnings_shown' => "⚠️ HIDE",
        'scam_hidden' => "SHOW SCAM",
        'scam_shown' => "HIDE SCAM",
        'rules' => "TERMS OF USE",
        'language' => "LANG",
        'network' => "NETWORK",
        'network_soon' => "🚧",
    ],
    'commands' => [
        'private' => [
            'start' => 'Update the Bot',
            'scan' => 'Scan token',
        ],
        'public' => [
            'p' => 'Get token price report',
            'h' => 'Get token holders report',
        ],
        'admin' => [
            // Не менять тут ничего местами
            'p' => 'Get token price report',
            'h' => 'Get token holders report',
            'settings' => 'Specify bot settings for chat',
            // 'network' => 'Set network for chat (e.g. /network ton)',
            // 'show_tips' => 'Show warnings',
            // 'hide_tips' => 'Hide warnings',
            // 'autopost_new_scam_on' => 'Show scam notifications',
            // 'autopost_new_scam_off' => 'Hide scam notifications',
            // 'set_ru_language' => 'Сменить на русский',
            // 'set_en_language' => 'Change to english',
        ],
    ],
];
