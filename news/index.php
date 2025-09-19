<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Новости");

// Включаем наш кастомный компонент
$APPLICATION->IncludeComponent(
    "yourcompany:news.list",
    ".default",
    [
        "IBLOCK_TYPE" => "news",
        "IBLOCK_ID" => "11",
        "NEWS_COUNT" => "10",
        "SORT_BY1" => "ACTIVE_FROM",
        "SORT_ORDER1" => "DESC",
        "SORT_BY2" => "SORT",
        "SORT_ORDER2" => "ASC",
        "FIELD_CODE" => ["NAME"],
        "PROPERTY_CODE" => ["COST", "FEATURE"],
        "SET_TITLE" => "N",
        "SET_BROWSER_TITLE" => "N",
        "SET_LAST_MODIFIED" => "N",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "ADD_SECTIONS_CHAIN" => "N",
        "HIDE_LINK_WHEN_NO_DETAIL" => "Y",
        "CHECK_DATES" => "Y",
    ],
    false
);
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>