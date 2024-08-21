<?php

return [
    'text' => [
        'lang' => "👇 Please choose your language.",
        'rules' => "<b>ПАВИЛА ПОЛЬЗОВАНИЯ:</b>
Используя данного Telegram бота, вы подтверждаете и соглашаетесь с тем, что использование бота осуществляется на ваш собственный риск. Создатели данного бота не несут ответственности за любые убытки или ущерб, которые могут возникнуть в результате его использования. Бот предоставляется в текущем виде, без каких-либо гарантий, явных или подразумеваемых. Пользователь несет ответственность за проверку достоверности предоставляемой информации и принятие необходимых мер предосторожности для защиты себя от возможных мошеннических действий и других рисков.",
        'spam' => "🏖 Не стоит отправлять так много сообщений. Возьму перерыв...",
        'home' => "

<b>🔎 Token Scanner</b> - <code>Проверка на скам. Полная информацию о токене. Инструмент для DYOR.</code>

<b>👀 Wallet Tracker</b> - <code>Отслеживание кошельков.</code>

<b>🗃 Blackbox</b> - <code>Отправьте информацию о мошеннических схемах или скамерах анонимно в RUGP.</code>

<b>🚨 Check My Wallet</b> - <code>Проверка своего кошелька на скам и уязвимости.</code>

<b>📚 Academy</b> - <code>Бесплатные обучающие материалы.</code>

<b>💡 GPT</b> - <code>Бесплатный GPT.</code>


   <a href='http://rugo.io'>WEB</a>    |    <a href='t.me/rugp_ton'>Telegram</a>    |     <a href='https://x.com/rugp_ton'>Twitter</a>     

",
        'token_scanner' => [
            'main' => "👇 Введи адрес токена или пула.",
            'pending' => "🔎 Сканирую. Отчет будет отправлен вам сообщением, как только будет готов.",
            'report' => [
                'text' => "



<b>ℹ️ ОБЩАЯ ИНФОРМАЦИЯ</b>

<b>:name | $:symbol</b>

<i>:description</i>

:is_known_master
:is_known_wallet
:is_revoked

<b>🔢 Кол-во токенов:</b> :supply
<b>👨‍👦‍👦 Кол-во холдеров:</b> :holders_count

🔄 <u><b>Биржи:</b> </u>
:pools

<u><b>Соцсети:</b></u>
:links

<u><b>Доверие сообщества:</b></u>
👍 <b>:likes_count</b> / <b>:dislikes_count</b> 👎

Нажми 🔎 для нового скана.

",
                'pool' => "				
<i><b>:name</b></i>:
├💵 Цена: <b>$:price</b>
:lp_burned
:lp_locked
:tax_buy
:tax_sell
",
                'link' => "<a href=':url'><b>:Label</b></a> ",
                'is_known_master' => [
                    'yes' => "✅ Проверенный код контракта",
                    'no' => "⚠️ Кастомный код контракта",
                ],
                'is_known_wallet' => [
                    'yes' => "✅ Проверенный код у кошелька",
                    'no' => "⚠️ Кастомный код у кошелька",
                ],
                'is_revoked' => [
                    'yes' => "✅ Права отозваны",
                    'no' => "⚠️ Права не отозваны",
                ],
                'lp_burned' => [
                    'yes' => "├✅  <i>LP сожжены</i>: <b>:value%</b>",
                    'no' => "├⚠️ <i>LP не сожжены</i>  ",
                ],
                'lp_locked' => [
                    'yes' => "├🔒 <i>LP заблокированы</i>: <b>:value% :type :unlocks <i>:dyor</i></b>",
                    'no' => "├🔒 <i>LP заблокированы</i>: <b>0%</b>",
                ],
                'tax_buy' => [
                    'unknown' => "<i>🤷‍♂️ Не удалось произвести проверку</i>",
                    'no' => "├<i>🤦🏻 Невозможно купить</i>",
                    'ok' => "├<i>✅ Налог на покупку</i>: <b>нет</b>",
                    'warning' => "├<i>⚠️ Налог на покупку</i>: <b>:value%</b>",
                    'danger' => "├<i>🚨 Налог на покупку</i>: <b>:value%</b>",
                ],
                'tax_sell' => [
                    'unknown' => "└<i>🤷‍♂️ Не удалось произвести проверку</i>",
                    'no' => "
<b>НЕВОЗМОЖНО ПРОДАТЬ</b> 

‼️HONEYPOT‼️SCAM‼️",
                    'ok' => "└<i>✅ Налог на продажу</i>: <b>нет</b>",
                    'warning' => "└<i>⚠️ Налог на продажу</i>: <b>:value%</b>",
                    'danger' => "└<i>❌ Налог на продажу</i>: <b>:value%</b>",
                ],
            ],
            'chart' => [
                'text' => "
				
📈 <b>ГРАФИК</b> <b>$:symbol</b>

:pools
",
                'pool' => "
<a href=':link'>:name</a>
💵 <i>Цена:</i> <b>$:price</b>
🏦 <i>FDV:</i> <b>$:fdv</b>
💦 <i>Ликвидность:</i> <b>$:reserve</b>
📉 <u><i>Изменение цены:</i></u>
├<i>(5м):</i> <b>:price_change_m5%</b>
├<i>(1ч):</i> <b>:price_change_h1%</b>
├<i>(6ч):</i> <b>:price_change_h6%</b>
└<i>(24ч):</i> <b>:price_change_h24%</b>
 
<i>Пул создан:</i> <b>:created_at</b>
",
            ],
            'holders' => [
                'text' => "
👨‍👦‍👦 <b>ХОЛДЕРЫ</b> <b>$:symbol</b>		
				
:holders

",
                'holder' => "<a href='tonviewer.com/:address'><i>:label</i></a>: <b>:balance (:percent%)</b>\n",
                'dex_lock_stake' => "DEX/LOCK/STAKE?",
            ],
            'volume' => [
                'text' => "
				
📊 <b>ОБЪЕМ</b> <b>$:symbol</b>	
				
:pools

",
                'pool' => "<a href=':link'>:name</a>
🔈 <u><i>Объем</i></u>
├<i>(5м): </i> <b>$:volume_m5</b> 
├<i>(1ч): </i> <b>$:volume_h1</b> 
├<i>(6ч): </i> <b>$:volume_h6</b> 
└<i>(24ч): </i> <b>$:volume_h24</b>
🔼 <u><i>Покупки</i></u>
├<i>(5м): </i> <b>:buys_m5</b> 
├<i>(1ч): </i> <b>:buys_h1</b> 
├<i>(6ч): </i> <b>:buys_h6</b> 
└<i>(24ч): </i> <b>:buys_h24</b>
🔽 <u><i>Продажи</i></u>
├<i>(5м): </i> <b>:sells_m5</b> 
├<i>(1ч): </i> <b>:sells_h1</b> 
├<i>(6ч): </i> <b>:sells_h6</b> 
└<i>(24ч): </i> <b>:sells_h24</b>

",
            ],
        ],
        'profile' => [
            'main' => "
<b>Язык</b>: <i>:language</i>
",
        ],
    ],
    'errors' => [
        'address' => [
            'invalid' => "Неверный адрес",
            'empty' => "По данному адресу ничего не найдено",
        ],
        'scan' => [
            'metadata' => "Невозможно получить информацию по токену :address. Попробуйте позже",
            'simulator' => "Невозможно произвести проверки по токену :address. Попробуйте позже",
        ],
    ],
    'buttons' => [
        'ru' => "🇷🇺 RUS",
        'en' => "🇺🇸 ENG",

        'back' => "Назад",
        'cancel' => "Отмена",
        'agree' => "Согласен",

        'token_scanner' => "🔎 Token Scanner",
        'wallet_tracker' => "👀 Wallet Tracker",
        'black_box' => "🗃 Black Box",
        'check_wallet' => "🚨 Check My Wallet",
        'academy' => "📚 Academy",
        'gpt' => "💡 GPTo",
        'profile' => "⚙️ Профиль",

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
