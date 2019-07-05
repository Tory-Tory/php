<?
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

use Bitrix\Main\Loader,
    Um\Main\Helpers;

//подключаем модуль синхронизации инфоблоков
Loader::includeModule("um.langsync");

if (Loader::includeModule("iblock")) {
    $props['WORKSHOP_STATUS']['VALUE'] = 'Черновик';
    $props['WORKSHOP_STATUS']['VALUE_ENUM'] = 'Черновик';
    //получаем массив списковых свойств
    $element_props_list = [];
    $element_props_desc = [];
    $props = array_intersect_key($props, $element_props);
    foreach ($props as $item) {
        switch ($item['PROPERTY_TYPE']) {
            case 'L':
                $element_props_list[] = $item['CODE'];
                break;
        }
        if (is_array($item['DESCRIPTION'])) {
            foreach ($item['DESCRIPTION'] as $desc) {
                $desc = trim($desc);
                $item['DESCRIPTION'] = $desc;
            }
        } else {
            $item['DESCRIPTION'] = trim($item['DESCRIPTION']);
        }
        if (!empty($item['DESCRIPTION'] && $item['CODE'] != 'GOOGLE_TOUR')) {
            $element_props_desc[] = $item['CODE'];
        }
    }

    foreach ($element_props_list as $list) {
        if (array_key_exists($list, $element_props)) {
            unset($element_props[$list]);
        }
    }
    foreach ($element_props_desc as $desc) {
        if (array_key_exists($desc, $element_props)) {
            unset($element_props[$desc]);
        }
    }

    //если англ версии нет, создаем элемент
    //получаем ID инфоблока в англ версии по переданному коду
    $iblock_id = Helpers::getByCodeTable($fields['IBLOCK_CODE'], 'dm');
    $codes = $element_props_list; //передаем списковые свойства

    $code_props = [];
    $value_enum = [];
    //делаем выборку ID и кода соответствующих свойств англ версии
    $prop_table = \Bitrix\Iblock\PropertyTable::getList([
        "filter" => [
            "CODE" => $codes,
            "=IBLOCK_ID" => $iblock_id,
        ],
        "select" => [
            "ID",
            "CODE",
        ],
    ]);
    while ($prop_table_arr = $prop_table->fetch()) {
        $code_props[$prop_table_arr["ID"]] = $prop_table_arr["CODE"];
        if ($props[$prop_table_arr["CODE"]]["VALUE_ENUM"]) {
            $value_enum[$prop_table_arr["ID"]] = $props[$prop_table_arr["CODE"]]["VALUE_ENUM"];
            //записываем англ ID  в массив свойств
        }
    }
    $code = [];

    //замена объектов в элементе
    for ($i = 0; $i < count($props['OBJECTS']['VALUE']); $i++) {

        $iblock = \Bitrix\Iblock\ElementTable::getList([
            "filter" => [
                "ID" => $props['OBJECTS']['VALUE'][$i],
            ],
            "select" => [
                "IBLOCK_ID",
            ],
        ])->fetch()["IBLOCK_ID"];

        $code = \Bitrix\Iblock\IblockTable::getList([
            "filter" => [
                "ID" => $iblock,
            ],
            "select" => [
                "CODE",
            ],
        ])->fetch()['CODE'];

        $RuIblock = \Bitrix\Iblock\IblockTable::getList([
            'filter' => [
                "CODE" => $code,
                "IBLOCK_TYPE_ID" => "um",
            ],
            'select' => [
                "ID",
            ],
        ])->fetch()["ID"];

        $arr_obj = CIBlockElement::getProperty(
            $RuIblock,
            $props['OBJECTS']['VALUE'][$i],
            [],
            $arFilter = ['CODE' => 'EN_ELEMENT']
        )->GetNext();

        if ($arr_obj['VALUE']) {
            $props['OBJECTS']['VALUE'][$i] = $arr_obj['VALUE'];
        } else {
            $props['OBJECTS']['VALUE'][$i] = null;
        }
    }

    if ($props['OBJECT']['VALUE']) {
        $iblock = \Bitrix\Iblock\ElementTable::getList([
            "filter" => [
                "ID" => $props['OBJECT']['VALUE'],
            ],
            "select" => [
                "IBLOCK_ID",
            ],
        ])->fetch()["IBLOCK_ID"];

        $code = \Bitrix\Iblock\IblockTable::getList([
            "filter" => [
                "ID" => $iblock,
            ],
            "select" => [
                "CODE",
            ],
        ])->fetch()['CODE'];

        $RuIblock = \Bitrix\Iblock\IblockTable::getList([
            'filter' => [
                "CODE" => $code,
                "IBLOCK_TYPE_ID" => "um",
            ],
            'select' => [
                "ID",
            ],
        ])->fetch()["ID"];

        $arr_obj = CIBlockElement::getProperty(
            $RuIblock,
            $props['OBJECT']['VALUE'],
            [],
            $arFilter = ['CODE' => 'EN_ELEMENT']
        )->GetNext();

        if ($arr_obj['VALUE']) {
            $props['OBJECT']['VALUE'] = $arr_obj['VALUE'];
        } else {
            $props['OBJECT']['VALUE'] = null;
        }

    }


    //массив кодов инфоблоков синхронизирующихся элементов и соответствующих их свойств
    $codes_block = [
        'HOUSES_NEAR' => 'houses',
        'near' => 'houses',
        'AUTHOR' => 'authors',
        'AUTHOR_USER' => 'authors',
        'HOUSES' => 'houses',
        'HOUSE' => 'houses',
        'PERIOD' => 'period',
        'ROUTES' => 'routes',
        'SIMILAR_ROUTES' => 'routes',
        'PERSONALITIES' => 'personalities',
        'MUSEUMS' => 'museums',
        'DOTS' => 'quest_dots',
        'MONUMENTS' => 'monuments',
    ];

    //формируем массив русских ИБ
    $RuIblock = [];
    foreach ($codes_block as $prop_code => $code_block) {
        $ibType = \Bitrix\Iblock\IblockTable::getList([
            'filter' => [
                "CODE" => $code_block,
            ],
            'select' => [
                "IBLOCK_TYPE_ID",
            ],
        ])->fetch()['IBLOCK_TYPE_ID'];
        $RuIblock[$prop_code] = \Bitrix\Iblock\IblockTable::getList([
            'filter' => [
                "CODE" => $code_block,
                "IBLOCK_TYPE_ID" => $ibType,
            ],
            'select' => [
                "ID",
            ],
        ])->fetch()['ID'];
    }

    //перебираем все элементы в синх массиве
    foreach ($codes_block as $prop_code => $code_block) {
        //если свойство множественное
        if (is_array($props[$prop_code]['VALUE'])) {
            $i = 0;
            //получаем английский элемент синхронизирующегося свойства
            foreach ($props[$prop_code]['VALUE'] as $item) {
                $elem[$prop_code] = CIBlockElement::getProperty(
                    $RuIblock[$prop_code],
                    $item,
                    [],
                    $arFilter = ['CODE' => 'EN_ELEMENT']
                )->GetNext();

                //если поле заполнено, то перезаписываем свойство
                if ($elem[$prop_code]['VALUE']) {
                    $props[$prop_code]['VALUE'][$i] = $elem[$prop_code]['VALUE'];
                } else {
                    $props[$prop_code]['VALUE'][$i] = null;
                }
                $i++;
            }
        } else {
            $elem[$prop_code] = CIBlockElement::getProperty(
                $RuIblock[$prop_code],
                $props[$prop_code]['VALUE'],
                [],
                $arFilter = ['CODE' => 'EN_ELEMENT'])->GetNext();
            if ($elem[$prop_code]['VALUE']) {
                $props[$prop_code]['VALUE'] = $elem[$prop_code]['VALUE'];
            } else {
                $props[$prop_code]['VALUE'][$i] = null;
            }
        }
    }

    //формируем массив свойств
    $new_props = [];
    $new_props['RU_ELEMENT'] = $fields['ID'];
    //для обычных свойств

    foreach ($element_props as $props_code => $value) {
        $new_props[$props_code] = $props[$props_code]['VALUE'];
        //имя автора записывается транслитом
        if ($prop_code = 'AUTHOR_NAME') {
            $new_props['AUTHOR_NAME'] = \CUtil::translit($props['AUTHOR_NAME']['VALUE'], 'ru',
                ['change_case' => false, 'replace_space' => ' ',]);
        }
    }

    //для свойств с описанием
    foreach ($element_props_desc as $props_desc_code) {
        //если свойство с описанием множественное
        if (is_array($props[$props_desc_code]['VALUE'])) {
            foreach ($props[$props_desc_code]['VALUE'] as $key => $value) {
                if ($props_desc_code == 'HOUSES' || $props_desc_code == 'MONUMENTS') {
                    $desc = explode('|', $props[$props_desc_code]['DESCRIPTION'][$key]);
                    $props[$props_desc_code]['DESCRIPTION'][$key] = $desc[0] . '|' . $desc[1] . '|';
                }
                $new_props[$props_desc_code][] = [
                    'VALUE' => $value,
                    'DESCRIPTION' => $props[$props_desc_code]['DESCRIPTION'][$key],
                ];
            }
        } else {
            if ($props_desc_code == 'HOUSES' || $props_desc_code == 'MONUMENTS') {
                $desc = explode('|', $props[$props_desc_code]['DESCRIPTION']);
                $props[$props_desc_code]['DESCRIPTION'] = $desc[0] . '|' . $desc[1] . '|';
            }
            $new_props[$props_desc_code] = [
                'VALUE' => $props[$props_desc_code]['VALUE'],
                'DESCRIPTION' => $props[$props_desc_code]['DESCRIPTION'],
            ];
        }
    }

    //для списковых свойств
    $keys = array_keys($value_enum);
    foreach ($keys as $key) {
        if (is_array($value_enum[$key])) {
            $deviation[$key] = $value_enum[$key];
            unset($value_enum[$key]);
        }
    }

    if ($deviation) {
        if ($props['BANNER_SECTIONS']) {
            $array = $props['BANNER_SECTIONS']['VALUE_XML_ID'];
            $val = [
                "XML_ID" => $props['BANNER_SECTIONS']['VALUE_XML_ID'],
            ];
        } else {
            $array = $deviation;
            $val = [
                "VALUE" => $deviation[$keys[$i]][$i],
            ];
        }

        for ($i = 0; $i < count($deviation); $i++) {
            $prop_enum = \Bitrix\Iblock\PropertyEnumerationTable::getList([
                "filter" => [
                    "=PROPERTY_ID" => $keys[$i],
                    $val,
                ],
                "select" => [
                    "ID",
                    "PROPERTY_ID",
                ],
            ]);

            while ($prop_enum_arr = $prop_enum->fetch()) {
                $new_props[$code_props[$prop_enum_arr['PROPERTY_ID']]][] = $prop_enum_arr["ID"];
            }
        }
    }

    $prop_enum = \Bitrix\Iblock\PropertyEnumerationTable::getList([
        "filter" => [
            "=PROPERTY_ID" => array_keys($value_enum),
            "VALUE" => $value_enum,
        ],
        "select" => [
            "ID",
            "PROPERTY_ID",
        ],
    ]);

    while ($prop_enum_arr = $prop_enum->fetch()) {
        $new_props[$code_props[$prop_enum_arr['PROPERTY_ID']]] = $prop_enum_arr["ID"];
    }

    //получаем ID английского раздела по коду
    $arr_group = [];
    $iblock_section = [];
    $groups = CIBlockElement::GetElementGroups($fields['ID']);
    while ($ar_group = $groups->Fetch()) {
        $arr_group[] = $ar_group['ID'];
    }
    if ($arr_group) {
        for ($i = 0; $i < count($arr_group); $i++) {
            $code = \Bitrix\Iblock\SectionTable::getList([
                'filter' => [
                    'ID' => $arr_group[$i],
                ],
                'select' => [
                    'CODE',
                ],
            ])->fetch()['CODE'];
            if ($code) {
                $iblock_section[] = \Bitrix\Iblock\SectionTable::getList([
                    'filter' => [
                        '=IBLOCK_ID' => $iblock_id,
                        'CODE' => $code,
                    ],
                    'select' => [
                        'ID',
                    ],
                ])->fetch()['ID'];
            }
        }
    }

    //формируем общий массив добавляемых полей
    $arLoadArray = [
        "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
        'IBLOCK_SECTION' => $iblock_section,
        "IBLOCK_ID" => $iblock_id,
        "PROPERTY_VALUES" => $new_props,
        "NAME" => $fields["NAME"],
        "CODE" => $fields["CODE"],
        "ACTIVE" => $fields['ACTIVE'],
        "DATE_CREATE" => $fields['DATE_CREATE'],
        'DATE_ACTIVE_FROM' => $fields["DATE_ACTIVE_FROM"],
        'DATE_ACTIVE_TO' => $fields["DATE_ACTIVE_TO"],
        "PREVIEW_PICTURE" => CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"] . CFile::GetPath($fields['PREVIEW_PICTURE'])),
        "DETAIL_PICTURE" => CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"] . CFile::GetPath($fields['DETAIL_PICTURE'])),
    ];

    //добавление элемента
    $ID = $el->Add($arLoadArray);
              
}
