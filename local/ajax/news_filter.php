<?php
define('BX_SESSION_NO_START', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

// Устанавливаем часовой пояс сервера
date_default_timezone_set('Europe/Moscow');

header('Content-Type: application/json; charset=utf-8');

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    echo json_encode(['error' => 'Неверный запрос']);
    exit();
}

use Bitrix\Main\Context;
use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock')) {
    echo json_encode(['error' => 'Модуль инфоблоков не установлен']);
    exit();
}

$request = Context::getCurrent()->getRequest();

if (!$request->isPost() || $request->get('action') !== 'filter_news') {
    echo json_encode(['error' => 'Неверный запрос']);
    exit();
}

$iblockId = (int)$request->getPost('IBLOCK_ID');
if (!$iblockId) {
    echo json_encode(['error' => 'ID инфоблока не указан']);
    exit();
}

$filter = [
    'IBLOCK_ID' => $iblockId,
    'ACTIVE' => 'Y',
];

// Фильтр по дате
$dateFilter = $request->getPost('filter_date');
if ($dateFilter === 'this_month') {
    $start = date('d.m.Y 00:00:00', strtotime('first day of this month'));
    $end = date('d.m.Y 23:59:59', strtotime('last day of this month'));
    $filter['>=DATE_ACTIVE_FROM'] = $start;
    $filter['<=DATE_ACTIVE_FROM'] = $end;
} elseif ($dateFilter === 'this_week') {
    $monday = strtotime('monday this week');
    $sunday = strtotime('sunday this week');
    $start = date('d.m.Y 00:00:00', $monday);
    $end = date('d.m.Y 23:59:59', $sunday);
    $filter['>=DATE_ACTIVE_FROM'] = $start;
    $filter['<=DATE_ACTIVE_FROM'] = $end;
}

// Поиск по названию
if ($searchName = trim($request->getPost('search_name'))) {
    $filter['%NAME'] = $searchName;
}

// Фильтр по стоимости
if ($costFrom = $request->getPost('cost_from')) {
    $filter['>=PROPERTY_COST'] = (float)$costFrom;
}
if ($costTo = $request->getPost('cost_to')) {
    $filter['<=PROPERTY_COST'] = (float)$costTo;
}

// Детальное логирование
$logData = [
    'dateFilter' => $dateFilter,
    'start' => isset($start) ? $start : null,
    'end' => isset($end) ? $end : null,
    'filter' => $filter,
    'request' => $request->getPostList()->toArray()
];
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/filter_debug.log', date('Y-m-d H:i:s') . " - " . print_r($logData, true) . "\n", FILE_APPEND);

// Получаем отфильтрованные элементы
$rsItems = CIBlockElement::GetList(
    ['DATE_ACTIVE_FROM' => 'DESC'],
    $filter,
    false,
    false,
    ['ID', 'NAME', 'DATE_ACTIVE_FROM', 'DETAIL_PAGE_URL', 'PROPERTY_COST', 'PROPERTY_FEATURE']
);

$items = [];
while ($item = $rsItems->GetNext()) {
    $items[] = [
        'ID' => (string)$item['ID'],
        'NAME' => (string)$item['NAME'],
        'DATE_ACTIVE_FROM' => (string)$item['DATE_ACTIVE_FROM'], // Для отладки
        'URL' => (string)$item['DETAIL_PAGE_URL'],
        'COST' => isset($item['PROPERTY_COST_VALUE']) ? (string)$item['PROPERTY_COST_VALUE'] : '',
        'FEATURE' => isset($item['PROPERTY_FEATURE_VALUE']) ? (string)$item['PROPERTY_FEATURE_VALUE'] : '',
    ];
}

// Логируем результат
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/filter_result.log', date('Y-m-d H:i:s') . " - " . print_r($items, true) . "\n", FILE_APPEND);

echo json_encode($items);
exit();