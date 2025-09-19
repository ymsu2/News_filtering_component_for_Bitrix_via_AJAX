<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

// Определяем, является ли запрос AJAX-запросом
$isAjaxRequest = false;
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    $isAjaxRequest = true;
}
?>
<div class="news-list">
    <!-- Форма фильтрации -->
    <form id="news-filter" style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
        <input type="hidden" name="IBLOCK_ID" value="<?=$arParams['IBLOCK_ID']?>">
        <div style="margin-bottom: 10px;">
            <input type="text" name="search_name" placeholder="Поиск по названию" style="width: 300px; padding: 5px;">
        </div>
        <div style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
            <input type="number" name="cost_from" placeholder="Стоимость от" min="0" style="width: 120px; padding: 5px;">
            <input type="number" name="cost_to" placeholder="Стоимость до" min="0" style="width: 120px; padding: 5px;">
        </div>
        <div style="margin-bottom: 10px;">
            <select name="filter_date">
                <option value="">За все время</option>
                <option value="this_month">За этот месяц</option>
                <option value="this_week">За эту неделю</option>
            </select>
        </div>
        <button type="submit" style="padding: 8px 16px; background: #007cba; color: white; border: none; cursor: pointer;">
            Применить фильтр
        </button>
        <button type="button" id="reset-filter" style="padding: 8px 16px; background: #ccc; color: #333; border: none; cursor: pointer; margin-left: 10px;">
            Сбросить фильтр
        </button>
    </form>

    <div id="news-results">
        <?if(!$isAjaxRequest): ?>
            <?foreach($arResult["ITEMS"] as $arItem):?>
                <?
                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                ?>
                <div class="news-item" id="<?=$this->GetEditAreaId($arItem['ID']);?>" style="margin: 15px 0; padding: 15px; border: 1px solid #e0e0e0;">
                    <!-- Название -->
                    <?if($arParams["DISPLAY_NAME"]!="N" && $arItem["NAME"]):?>
                        <h3>
                            <?if(!$arParams["HIDE_LINK_WHEN_NO_DETAIL"] || ($arItem["DETAIL_TEXT"] && $arResult["USER_HAVE_ACCESS"])):?>
                                <a href="<?echo $arItem["DETAIL_PAGE_URL"]?>"><?echo $arItem["NAME"]?></a>
                            <?else:?>
                                <?echo $arItem["NAME"]?>
                            <?endif;?>
                        </h3>
                    <?endif;?>
                    <!-- Стоимость -->
                    <?if(isset($arItem["PROPERTIES"]["COST"]["VALUE"])):?>
                        <p><strong>Стоимость:</strong> <?=$arItem["PROPERTIES"]["COST"]["VALUE"]?> ₽</p>
                    <?endif;?>
                    <!-- Особенность -->
                    <?if(isset($arItem["PROPERTIES"]["FEATURE"]["VALUE"])):?>
                        <p><strong>Особенность:</strong> <?=$arItem["PROPERTIES"]["FEATURE"]["VALUE"]?></p>
                    <?endif;?>
                </div>
            <?endforeach;?>
        <?endif;?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('news-filter');
    const results = document.getElementById('news-results');
    const resetButton = document.getElementById('reset-filter');

    // Функция для сброса фильтра
    resetButton.addEventListener('click', function() {
        form.reset();
        window.location.reload(); // Полная перезагрузка — показывает все элементы
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        formData.append('action', 'filter_news');

        results.innerHTML = '<p style="color: #666;">Загрузка...</p>';

        // ✅ ВАЖНО: ЗАПРОС ИДЕТ НЕ В КОМПОНЕНТ, А В ОТДЕЛЬНЫЙ ФАЙЛ!
        fetch('/local/ajax/news_filter.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text); // Для отладки
            try {
                const data = JSON.parse(text);
                if (data.error) {
                    results.innerHTML = '<p style="color: red;">Ошибка: ' + escapeHtml(data.error) + '</p>';
                    return;
                }
                if (data.length === 0) {
                    results.innerHTML = '<p>Новости не найдены</p>';
                    return;
                }
                results.innerHTML = ''; // Очистка
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'news-item';
                    div.style.margin = '15px 0';
                    div.style.padding = '15px';
                    div.style.border = '1px solid #e0e0e0';
                    let html = '';
                    if (item.NAME) {
                        html += '<h3><a href="' + escapeHtml(item.URL || '') + '">' + escapeHtml(item.NAME || '') + '</a></h3>';
                    }
                    if (item.COST !== undefined && item.COST !== null) {
                        html += '<p><strong>Стоимость:</strong> ' + escapeHtml(item.COST) + ' ₽</p>';
                    }
                    if (item.FEATURE !== undefined && item.FEATURE !== null) {
                        html += '<p><strong>Особенность:</strong> ' + escapeHtml(item.FEATURE) + '</p>';
                    }
                    div.innerHTML = html;
                    results.appendChild(div);
                });
            } catch (e) {
                console.error('Invalid JSON:', text);
                results.innerHTML = '<p style="color: red;">Ошибка загрузки данных: Неверный формат ответа от сервера</p>';
            }
        })
.catch(error => {
    results.innerHTML = '<p style="color: red;">Ошибка загрузки данных: ' + escapeHtml(error.message) + '</p>';
    console.error('Error:', error);
    // Логирование ошибки в консоль
    fetch('/local/ajax/log_error.php', {
        method: 'POST',
        body: JSON.stringify({ error: error.message, url: '/local/ajax/news_filter.php' }),
        headers: { 'Content-Type': 'application/json' }
    }).catch(console.error);
});

    });

    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        if (typeof text !== 'string') {
            text = String(text);
        }
        const map = {
            '&': '&amp;',
            '<': '<',
            '>': '>',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>