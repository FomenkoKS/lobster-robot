<?php
include '../Telegram.php';
require_once('config.php');


$telegram = new Telegram(BOT_TOKEN);
$text = $telegram->Text();
$chat_id = $telegram->ChatID();
$message = $telegram->Message();

function debug($arg, $telegram)
{
    $telegram->sendMessage([
        'chat_id' => 32512143,
        'text' => print_r($arg, true),
    ]);
}

function checkLinkText($linkText)
{
    $exceptions = ['youtu', 'yandex', 'instagr','spotify.com'];
    foreach ($exceptions as $word) {
        if (stripos($linkText, $word) !== false) {
            return false; // Слово найдено, возвращаем true
        }
    }
    return true; // Слово не найдено, возвращаем false
}

if (isset($message['reply_to_message'])) {
    $chat_id = $chat_id;
    $user_id = $message['from']['id'];

    if (!isset($message['sender_chat'])) {
        $member = $telegram->getChatMember([
            'chat_id' => $chat_id,
            'user_id' => $user_id
        ]);

        if ($member['result']['status'] == 'left') {
            $spam = 0;

            if (isset($message['caption_entities'])) {
                $entities = $message['caption_entities'];
            } elseif (isset($message['entities'])) {
                $entities = $message['entities'];
            }

            if (isset($entities)) {
                $types = array_column($entities, 'type');
                $ban_entity_types = ['text_link', 'url'];

                foreach ($ban_entity_types as &$ban_entity_type) {
                    if (array_search($ban_entity_type, $types) !== FALSE) {
                        if (checkLinkText($message['text'])) {
                            $spam++;
                        }
                    }
                }
            }

            if ($spam > 0) {
                $dests = [32512143, 341831513];
                foreach ($dests as &$dest) {
                    $telegram->sendMessage([
                        'chat_id' => $dest,
                        'text' => 'Spam detected @' . $message['reply_to_message']['sender_chat']['username'],
                    ]);

                    $telegram->forwardMessage([
                        'chat_id' => $dest,
                        'from_chat_id' => $chat_id,
                        'message_id' => $message['message_id']
                    ]);
                }

                $telegram->deleteMessage([
                    'chat_id' => $chat_id,
                    'message_id' => $message['message_id']
                ]);
            }
        }
    }
}