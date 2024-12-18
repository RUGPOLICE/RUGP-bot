<?php

return [
    'text' => [
        'lang' => "👇 Please choose your language.",
        'rules' => "<b>ПРАВИЛА ПОЛЬЗОВАНИЯ:</b>
Используя данного Telegram бота, вы подтверждаете и соглашаетесь с тем, что использование бота осуществляется на ваш собственный риск.
Создатели данного бота не несут ответственности за любые убытки или ущерб, которые могут возникнуть в результате его использования.
Бот предоставляется в текущем виде, без каких-либо гарантий, явных или подразумеваемых.
Пользователь несет ответственность за проверку достоверности предоставляемой информации и принятие необходимых мер предосторожности для защиты себя от возможных мошеннических действий и других рисков.",
        'spam' => "🏖 Не стоит отправлять так много сообщений. Возьму перерыв...",
        'home' => "

<b>RUGP бот - сервис безопасности для цифровых активов.</b>

Основные инструменты:

<b>🔎 Token Scanner</b> - <code>Инструмент для получения информации о токене (проверка на скам, холдеры, цена, объем).</code>
BETA 0.3

<b>👀 Wallet Tracker</b> - <code>С помощью этого инструмента Вы сможете следить за любыми движениями на кошельках (инвестора, крупного холдера проекта или создателя монеты).</code>
🔜

<b>🗃 Blackbox</b> - <code>Отправьте информацию о мошеннических схемах или скамерах анонимно в RUGP.
Нашли баг или ошибку в нашем сервисе? Используй данную функцию, и мы все рассмотрим 📤</code>

<b>🚨 Check My Wallet</b> - <code>Не знаете что за монета или НФТ у Вас на кошельке и можно ли с ней взаимодействовать? Проверить можно тут.</code>
🔜

<b>📚 Academy</b> - <code> Не понятен термин или слово? В этом разделе Вы найдете обучающие материалы. </code>
🔜

<b>💡 RUGPto</b> - <code>Бесплатный GPT4o. Иногда интересующую вас информацию можно спросить у него. Вам доступно :requests_count запросов каждый день.</code>

<a href='http://rugp.io'>WEB</a> | <a href='https://t.me/rugpolicenews'>Telegram</a> | <a href='https://x.com/rugp_ton'>Twitter</a>

© RUGP - анти-скам сообщество и полезные инструменты.
Если нашли баг в боте, просьба сообщить в tg чат проекта. 🙏",
        'token_scanner' => [
            'main' => "👇 Введите название с <b>$</b>, адрес токена, пула или ссылку с биржи.\nПриоритетная сеть: <b>:network</b>",
            'examples' => "\n\nПримеры:\nНазвание токена: <code>\$rugp</code>\nАдрес токена: <code>EQAXUTLNVMa_Hbm_GX2NzxvtoOA_iJU2d5Tf0E715MY_RUGP</code>\nАдрес пула: <code>EQAarUZyr_WFG3sdV9wY6ft5a-Ugm1_bbHjNKrSC32UzI_As</code>\nСсылка с биржи: <code>https://www.geckoterminal.com/ton/pools/EQAarUZyr_WFG3sdV9wY6ft5a-Ugm1_bbHjNKrSC32UzI_As</code>",
            'pending' => "🔎 Сканирую. Отчет будет отправлен вам сообщением, как только будет готов.",
            "watermark" => "
<a href='http://rugp.io'>WEB</a> | <a href='https://t.me/rugpolicenews'>Telegram</a> | <a href='https://x.com/rugp_ton'>Twitter</a>
© RUGP
",
            'report' => [
                'text' => "
<b>ℹ️ ОБЩАЯ ИНФОРМАЦИЯ</b>

<b>:name | $:symbol on :NETWORK</b>
<code>:address</code>\n:alert:description_title<i>:description</i>

:is_known_master
:is_known_wallet

:is_revoked:is_revoked_warning

<b>🔢 Кол-во монет:</b> :supply
<b>👨‍👦‍👦 Кол-во холдеров:</b> :holders_count
<b>🛢 Market Cap:</b> $:market_cap

🔄 <u><b>Биржи:</b></u>
:pools:alert:lp_burned_warning:links_title:links
<u><b>Доверие сообщества:</b></u>
👍 <b>:likes_count</b> / <b>:dislikes_count</b> 👎
:is_finished:watermark",
                'pool' => "
<a href=':link'><i><b>:name</b></i></a>:
├💵 Цена: <b>$:price</b>
:tax_buy
:tax_sell
:lp_burned:lp_locked
",
                'link' => "<a href=':url'><b>:Label</b></a> ",
                'links_title' => "\n<u><b>Соцсети:</b></u>\n",
                'description_title' => "\n<u><b>Описание:</b></u>\n",
                'is_finished' => "\nНажми 🔎 для нового скана.",
                'is_known_master' => [
                    'yes' => "✅ Стандартный код контракта",
                    'no' => "⚠️ Необычный код контракта",
                    'scan' => "🔎 Проверяю...",
                ],
                'is_known_wallet' => [
                    'yes' => "✅ Стандартный код кошелька",
                    'no' => "⚠️ Необычный код кошелька",
                    'scan' => "🔎 Проверяю...",
                ],
                'is_revoked' => [
                    'yes' => "✅ Права отозваны.",
                    'no' => "⚠️ <b>Права не отозваны!</b>",
                ],
                'is_revoked_warning' => [
                    'yes' => "\nНельзя добавить количество монет, изменить % или запретить продажу.",
                    'no' => "\nМожно добавить количество монет, изменить % или запретить продажу.\nПокупайте только если уверены в проекте.",
                ],
                'lp_burned' => [
                    'yes' => "├✅  <i>LP сожжены</i>: <b>:value%</b>",
                    'no' => "├⚠️ <i>LP не сожжены</i>",
                    'scan' => "├🔎 <i>Проверяю...</i>",
                    'warning' => "
⚠️ <b>НЕ</b> вся ликвидность (LP) сожжена или заблокирована.
- Если доверяете проекту, то несущественно.
- Подробнее -> ♻️.
",
                ],
                'lp_locked' => [
                    'yes' => "\n└🔒 <i>LP заблокированы</i>\n<b>:value% на <a href=':link'>:type</a></b>:unlocks",
                    'no' => "\n└🔒 <i>LP не заблокированы</i>",
                    'multiple' => "\n└🔒 <i>Несколько локов (:value%) -> ♻️</i>:examples",
                    'examples' => " В т.ч.:",
                    'example' => "\n- <a href=':link'>:type</a> (:value%:unlocks)",
                    'scan' => "\n└🔎 <i>Проверяю...</i>",
                    'burned' => "",
                    'unlocks' => " до :value",
                    'dyor' => "/ more locks! DYOR",
                ],
                'tax_buy' => [
                    'scan' => "├<i>🔎️ Проверяю...</i>",
                    'unknown' => "├<i>🤷‍♂️ Проверка не удалась</i>",
                    'no' => "├<i>🤦🏻 Невозможно купить</i>",
                    'ok' => "├<i>✅ % на покупку</i>: <b>нет</b>",
                    'warning' => "├<i>⚠️ % на покупку</i>: <b>:value%</b>",
                    'danger' => "├<i>🚨 % на покупку</i>: <b>:value%</b>",
                ],
                'tax_sell' => [
                    'scan' => "├<i>🔎️ Проверяю...</i>",
                    'unknown' => "├<i>🤷‍♂️ Проверка не удалась</i>",
                    'no' => "└<i>🤦🏻 Невозможно продать</i>",
                    'ok' => "├<i>✅ % на продажу</i>: <b>нет</b>",
                    'warning' => "├<i>⚠️ % на продажу</i>: <b>:value%</b>",
                    'danger' => "├<i>❌ % на продажу</i>: <b>:value%</b>",
                ],
                'alerts' => [
                    'is_warn_honeypot' => "\n❌HONEYPOT❌SCAM❌\n",
                    'is_warn_rugpull' => "\n⁉️ОСТОРОЖНО, ВЕРОЯТНО RUGPULL⁉️\n",
                    'is_warn_original' => "\n✅✅ОРИГИНАЛЬНЫЙ ТОКЕН✅✅\n",
                    'is_warn_scam' => "\n‼️SCAM‼️\n",
                    'is_warn_liquidity' => "\n‼️ОСТОРОЖНО, НИЗКАЯ ЛИКВИДНОСТЬ‼️\n",
                ],
            ],
            'chart' => [
                'text' => "

📈 <b>ГРАФИК</b> <b>$:symbol</b>

:pools:warning:warnings
:watermark
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

<i>Пул создан:</i> <b>:created_at</b>
",
                'warning' => "⚠️ Обратите внимание на соотношение покупок и продаж.\nЕсли много покупок и мало продаж, то возможно скам!",
                'warnings' => "\n\n⚙️ Выключить предупреждения в отчетах можно в настройках профиля.",
                'clock' => "Сначала нажмите на часы",
            ],
            'holders' => [
                'text' => "
<b>$:symbol</b>

👨‍👦‍👦 <b>ТОП ХОЛДЕРОВ (:actual ИЗ :total)</b>

:holders
:pools:warning
:watermark
",
                'pool' => "💧 <b>Ликвидность на</b> <a href='https://tonviewer.com/:address?section=holders'><u>:name</u></a>\n\n:holders\n",
                'holder' => "<b>:percent%</b> -> <a href=':address'><i>:label</i></a>\n",
                'dex_lock_stake' => "DEX/LOCK/STAKE?",
                'warning' => "🔥 zero-address - кошелек для сжигания.
🔒 DEX/LOCK/STAKE - монеты или ликвидность находятся на контракте биржи, сервиса блокировки или другом сервисе.
🔒 tinu-locker - кошелек для лока монет.
⚠️ MEXC, Bybit или OKX в холдерах у новой монеты с небольшим объемом = СКАМ!\n"
            ],
        ],
        'profile' => [
            'main' => "
<b>Язык</b>: <i>:language</i>
",
            'language' => "Выберите язык",
        ],
        'settings' => [
            'main' => "
🔎 RUGP Token Scanner - Инструмент для получения информации о токене (проверка на скам, холдеры, цена, объем).
BETA 0.3
<a href='http://rugp.io'>WEB</a> | <a href='https://t.me/rugpolicenews'>Telegram</a> | <a href='https://x.com/rugp_ton'>Twitter</a>


<u>ТЕКУЩИЕ НАСТРОЙКИ</u>

⚠️ Пояснения: <b>:is_show_warnings</b>
🅰 Автопост новых скам токенов в боте: <b>:is_show_scam</b>
Язык: <b>:language</b>
Сеть: <b>:network</b>
",
            'is_show_warnings' => [
                'yes' => "ВКЛ",
                'no' => "ВЫКЛ",
            ],
            'is_show_scam' => [
                'yes' => "ВКЛ",
                'no' => "ВЫКЛ",
            ],
            'blank_network' => "Не выбрана",
        ],
        'scanner_settings' => [
            'main' => "
⚠️ Пояснения: <b>:is_show_warnings</b>
🅰 Автопост новых скам токенов в боте: <b>:is_show_scam</b>
Сеть: <b>:network</b>
",
            'is_show_warnings' => [
                'yes' => "ВКЛ",
                'no' => "ВЫКЛ",
            ],
            'is_show_scam' => [
                'yes' => "ВКЛ",
                'no' => "ВЫКЛ",
            ],
            'network' => "Выберите сеть\n⚠️ Все сети кроме TON в тестовом режиме. Ждите дальнейших обновлений.",
            'blank_network' => "Не выбрана",
        ],
        'group' => "
🔎 RUGP Token Scanner - Инструмент для получения информации о токене (проверка на скам, холдеры, цена, объем).
BETA 0.3
<a href='http://rugp.io'>WEB</a> | <a href='https://t.me/rugpolicenews'>Telegram</a> | <a href='https://x.com/rugp_ton'>Twitter</a>

Для корректной работы бота, добавьте в группу и назначьте администратором.

Сканирование в чате по тикерам и адресу.
Пример:
\$NOT или EQAvlWFDxGF2lXm67y4yzC17wYKD9A0guwPkMs1gOsM__NOT


Список команд администратора:
/settings - текущие настройки бота.
/show_tips - вкл пояснений в отчетах.
/hide_tips - выкл .
/autopost_new_scam_on - вкл автопост новых скам монет в чат (по умолчанию выкл).
/autopost_scam_off - выкл.
/set_**_language - выбор языка.
/network - выбор приоритетной сети.

Основной бот --> @rugpbot
",
    ],
    'errors' => [
        'address' => [
            'invalid' => "🤷‍♂️ Неверный адрес",
            'symbol' => "🤷‍♂️ Токен не найден, попробуйте ввести адрес монеты",
            'empty' => "🤷‍♂️ По данному адресу ничего не найдено.\nВозможные причины: неверный адрес, удаленный скам или долгое время нет покупок и/или продаж.",
            'retype' => "🤷‍♂️ Некорректный запрос.\nПожалуйста, введите тикер с $ или адрес или ссылку с биржи.",
        ],
        'scan' => [
            'metadata' => "🚧 Невозможно получить информацию по токену :address. Попробуйте позже",
            'simulator' => "🚧 Невозможно произвести проверки по токену :address. Попробуйте позже",
            'fail' => "🚧 Внутренняя ошибка при сканировании :address. Попробуйте позже",
        ],
    ],
    'buttons' => [
        'ru' => "🇷🇺 RUS",
        'en' => "🇺🇸 ENG",

        'back' => "Назад",
        'cancel' => "Отмена",
        'ok' => "ОК",
        'agree' => "🤝 Согласен",

        'token_scanner' => "🔎 Token Scanner",
        'wallet_tracker' => "🔜 Wallet Tracker",
        'black_box' => "🗃️ Black Box",
        'check_wallet' => "🔜 Check My Wallet",
        'academy' => "🔜 Academy",
        'gpt' => "💡 RUGPto",
        'profile' => "⚙️ Профиль",

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

        'warnings_hidden' => "⚠️",
        'warnings_shown' => "⚠️",
        'scam_hidden' => "🅰",
        'scam_shown' => "🅰",
        'rules' => "Правила",
        'language' => "Язык",
        'network' => "Сеть",
        'network_soon' => "🚧",
    ],
    'commands' => [
        'private' => [
            'start' => 'На главную',
            'scan' => 'Сканировать токен',
            'bb' => 'Blackbox',
            'gpt' => 'RUGPto',
        ],
        'public' => [
            'p' => 'Получить отчет о цене токена',
            'h' => 'Получить отчет о холдерах токена',
        ],
        'admin' => [
            // Не менять тут ничего местами
            'p' => 'Получить отчет о цене токена',
            'h' => 'Получить отчет о холдерах токена',
            'settings' => 'Текущие настройки бота',
            // 'network' => 'Указать приоритетную сеть (например /network ton)',
            // 'show_tips' => 'Вкл пояснения в отчетах',
            // 'hide_tips' => 'Выкл пояснений в отчетах',
            // 'autopost_new_scam_on' => 'Вкл автопост новых скам монет в чат',
            // 'autopost_new_scam_off' => 'Выкл автопост новых скам монет в чат',
            // 'set_ru_language' => 'Сменить на русский',
            // 'set_en_language' => 'Change to english',
        ],
    ],
];
