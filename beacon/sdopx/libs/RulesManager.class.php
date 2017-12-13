<?php

namespace sdopx\libs {


    class RulesManager {

        static public $isload = false;
        static public $names = null;
        static public $count = 0;
        static public $left_delimiter = '{';
        static public $right_delimiter = '}';

        //<editor-fold defaultstate="consts" desc="常量区">
        const Init = 'init';
        const Init_Block = 'initbloak';
        //符号------------
        const Symbol = 'symbol'; //符号
        const Comma = 'comma'; //逗号
        const Colons = 'colons'; //冒号
        const Semicolon = 'semi'; //分号
        const DoubleArrow = 'dbarrow'; //双箭头
        const Assign = 'assign'; //赋值
        const Not = 'not'; //!
        const PrefixIncDec = 'preincdec'; //++ --
        const SuffixIncDec = 'sufincdec';
        //集合--------------
        //单引号
        const Open_SingleQuotes = 'opsqut';
        const Close_SingleQuotes = 'clsqut';
        const SingleQuotesString = 'sqstr';
        const In_SingleQuotes = 'in_squt';
        //双引号
        const Open_DoubleQuotes = 'op_dbq';
        const Close_DoubleQuotes = 'cl_dbq';
        const DoubleQuotesString = 'dbqstr';
        const In_DoubleQuotes = 'in_dbq';
        //双引号内定界符
        const Open_Delimiter = 'op_dlim';
        const Close_Delimiter = 'cl_dlim';
        const In_Delimiter = 'in_dlim';
        //静态函数
        const Open_StaticFunc = 'op_sfunc';
        const Close_StaticFunc = 'cl_sfunc';
        const In_StaticFunc = 'in_sfunc';
        //函数
        const Open_Func = 'op_func';
        const Close_Func = 'cl_func';
        const In_Func = 'in_func';
        //小括号
        const Open_Parentheses = 'op_pare';
        const Close_Parentheses = 'cl_pare';
        const In_Parentheses = 'in_pare';
        //中括号
        const Open_Brackets = 'op_brak'; //中括号
        const Close_Brackets = 'cl_brak';
        const In_Brackets = 'in_brak';
        //数组
        const Open_Array = 'op_arr';
        const Close_Array = 'cl_arr';
        const In_Array = 'in_arr';
        //标签属性
        const Open_TagAttr = 'op_tagattr';
        const Close_TagAttr = 'cl_tagattr';
        const In_TagAttr = 'in_tagattr';
        //标签
        const Open_Tag = 'op_tag';
        const Open_SpecialTag = 'op_sptag'; //特殊标签
        const Open_SimpleTag = 'op_simpletag'; //简单标签
        const Open_ExtendTag = 'op_exttag'; //继承标签
        const Open_EndTag = 'op_endtag'; //配套结束标签
        const In_Tag = 'in_tag';
        const In_ExtendTag = 'in_exttag';
        //模板
        const Open_Tpl = 'op_tpl';
        const Close_Tpl = 'cl_tpl';
        const In_Tpl = 'in_tpl';
        //配置项
        const Open_Config = 'op_cfg';
        const Close_Config = 'cl_cfg';
        const Init_Config = 'init_cfg';
        //模板块
        const Open_Brock = 'op_block';
        const Close_Brock = 'cl_block';
        const In_Brock = 'in_block';
        //注释
        const Open_Comment = 'op_comment';
        const Close_Comment = 'cl_comment';
        const Init_Comment = 'init_comm';
        //HTML
        const Open_Html = 'op_html';
        const Finish = 'finish';
        //其他
        const KeyWord = 'keyword';
        const ModifiersFunc = 'modfunc'; //修饰函数
        const In_ModifiersFunc = 'in_modfunc';
        //数据类型---
        const Number = 'num';
        const Variable = 'var';
        const StaticVariable = 'svar';
        const Constant = 'const';
        //变量扩展
        const VariableArrow = 'vkey'; //变量的属性->key
        const ArrowFuncOpen = 'vfunc';
        const VariablePoint = 'pkey'; //变量的属性.key
        const VariableAttr = 'akey'; //数据属性@key
        const ConfigKey = 'cfgkey'; //配置键名
        const ConfigKeyPoint = 'cfgpkey'; //配置点键名
        const Single_TagAttr = 'singtagattr';
        const ExpressionVar = 'expvar';
        const Init_Literal = 'literal';

        static public $error_name = [
            self::In_SingleQuotes => '单引号 \' 没有找到闭合的单引号.',
            self::In_DoubleQuotes => '双引号 " 没有找到闭合的双引号.',
            self::In_Delimiter => '双引号内的分解符 · 没有找到闭合的定界符.',
            self::In_StaticFunc => '静态函数没有找到闭合的右括号 ) .',
            self::In_Func => '函数没有找到闭合的右括号 ) .',
            self::In_Parentheses => '小括号（） 没有找到闭合的右括号 ) .',
            self::In_Brackets => '中括号 [] 没有找到闭合的右括号 ] .',
            self::In_Array => '数组格式书写有误，没有找到数组完整结束符号 .',
            self::In_TagAttr => '标签的属性 书写不符合要求，无法结束.',
            self::In_Tag => '没有找到标签结束符号.',
            self::In_ExtendTag => '没有找到标签结束符号.',
            self::In_Tpl => '没有找到模板结束符号 .',
            self::In_Brock => '没有找到block模板块结束符号 .',
        ];

        public static function getError($key) {
            return isset(self::$error_name[$key]) ? self::$error_name[$key] : '语法错误，不可提前关闭。';
        }

        //</editor-fold> 
        public static function load() {
            if (self::$isload) {
                return;
            }
            self::$isload = true;
            self::$names = [
                //<editor-fold defaultstate="collapsed" desc="运算符号">
                self::Symbol => [
                    'rule' => '===|\!==|==|\!=|>=|\sge\s|<=|\sle\s|>|\sgt\s|<|\slt\s|%|\smod\s|&&|\sand\s|\|\||\sor\s|\sxor\s|[+-]|[*\/%]|&|\s\|\s',
                    'token' => 'symbol',
                    'next' => [
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::StaticVariable => 0,
                        self::Open_Func => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 4, //前面必须带空格
                    ]
                ],
                //</editor-fold> 
                //<editor-fold defaultstate="collapsed" desc="冒号">
                self::Colons => array(
                    'rule' => ':',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::StaticVariable => 0,
                        self::Open_Func => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0,
                    )
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="逗号">
                self::Comma => array(
                    'rule' => ',',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::StaticVariable => 0,
                        self::Open_Func => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0,
                    )
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="分号">
                self::Semicolon => array(
                    'rule' => ';',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::StaticVariable => 0,
                        self::Open_Func => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0,
                    )
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="赋值">
                self::Assign => array(
                    'rule' => '=',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Parentheses => 0,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="前置非">
                self::Not => array(
                    'rule' => '\!',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 1,
                        self::Variable => 1, //变量
                        self::Open_SingleQuotes => 1,
                        self::Open_DoubleQuotes => 1,
                        self::Open_StaticFunc => 1,
                        self::StaticVariable => 1,
                        self::Open_Func => 1,
                        self::Open_Parentheses => 1,
                        self::Open_Array => 1,
                        self::Constant => 1,
                        self::Not => 1,
                        self::PrefixIncDec => 1,
                    )
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="前自加自减">
                self::PrefixIncDec => array(
                    'rule' => '\+\+|\-\-',
                    'token' => 'code',
                    'next' => array(
                        self::Variable => 1, //变量
                        self::StaticVariable => 1,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="后自加自减">
                self::SuffixIncDec => array(
                    'rule' => '\+\+|\-\-',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::Symbol => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="双箭头">
                self::DoubleArrow => array(
                    'rule' => '=>',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="打开模板标记">
                self::Open_Tag => array(
                    'rule' => '(?:\w+:)?\w+\s+',
                    'token' => 'tagname',
                    'next' => array(
                        self::Open_TagAttr => 6,
                        self::Single_TagAttr => 6,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="打开特殊标记">
                self::Open_SpecialTag => array(
                    'rule' => '(?:if|else\s*if|for|foreach)\s+',
                    'token' => 'tagname',
                    'next' => array(
                        self::Open_TagAttr => 6,
                        self::Open_Func => 0,
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::StaticVariable => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Single_TagAttr => 6,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                    'open' => self::In_Tag, //进入标签属性
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="单标记">
                self::Open_SimpleTag => array(
                    'rule' => '(?:\w+:)?\w+',
                    'token' => 'tagname',
                    'next' => array(
                        self::Close_Tpl => array(0, self::In_Tpl),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="继承标签">
                self::Open_ExtendTag => array(
                    'rule' => 'extends\s+',
                    'token' => 'tagname',
                    'next' => array(
                        self::Open_TagAttr => 6,
                        self::Single_TagAttr => 6,
                    ),
                    'byend' => true,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="尾部标签">
                self::Open_EndTag => array(
                    'rule' => '/(?:\w+:)?\w+',
                    'token' => 'tagclose',
                    'next' => array(
                        self::Close_Tpl => array(0, self::In_Tpl),
                    )
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="静态函数开">
                self::Open_StaticFunc => array(
                    'rule' => '\w+::\w+\(',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Close_StaticFunc => array(0, self::In_StaticFunc),
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                    'open' => self::In_StaticFunc,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="静态函数闭合">
                self::Close_StaticFunc => array(
                    'rule' => '\)',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Symbol => 0,
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::ModifiersFunc => 1,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                    'close' => self::In_StaticFunc,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="函数开">
                self::Open_Func => array(
                    'rule' => '\w+\(',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Close_Func => array(0, self::In_Func),
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                    'open' => self::In_Func,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="函数闭合">
                self::Close_Func => array(
                    'rule' => '\)',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Symbol => 0,
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::ModifiersFunc => 1,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                    'close' => self::In_Func,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="小括号开">
                self::Open_Parentheses => array(
                    'rule' => '\(',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Close_Parentheses => array(0, self::In_Parentheses),
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                    'open' => self::In_Parentheses,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="小括号闭">
                self::Close_Parentheses => array(
                    'rule' => '\)',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Symbol => 0,
                        self::ArrowFuncOpen => 1,
                        self::VariableArrow => 1,
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                    'close' => self::In_Parentheses,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="中括号开">
                self::Open_Brackets => array(
                    'rule' => '\[',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Parentheses => 0,
                        self::Close_Brackets => array(0, self::In_Brackets),
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                    'open' => self::In_Brackets,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="中括号闭">
                self::Close_Brackets => array(
                    'rule' => '\]',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::VariablePoint => 1,
                        self::ArrowFuncOpen => 1,
                        self::VariableArrow => 1,
                        self::Symbol => 0,
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        //以下是结束后标记
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                    'close' => self::In_Brackets,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="数组开">
                self::Open_Array => array(
                    'rule' => '\[',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Array => 0,
                        self::Open_Parentheses => 0,
                        self::Close_Array => array(0, self::In_Array),
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                    'open' => self::In_Array,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="数组闭">
                self::Close_Array => array(
                    'rule' => '\]',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                    'close' => self::In_Array,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="单引号打开">
                self::Open_SingleQuotes => array(
                    'rule' => '\'',
                    'token' => 'dyh_string',
                    'next' => array(
                        self::Close_SingleQuotes => array(1, self::In_SingleQuotes),
                        self::SingleQuotesString => 1, //变量
                    ),
                    'open' => self::In_SingleQuotes,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="单引号关闭">
                self::Close_SingleQuotes => array(
                    'rule' => '\'',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::DoubleArrow => 0,
                        self::Symbol => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                    'close' => self::In_SingleQuotes,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="双引号打开">
                self::Open_DoubleQuotes => array(
                    'rule' => '"',
                    'token' => 'syh_string',
                    'next' => array(
                        self::Close_DoubleQuotes => array(1, self::In_DoubleQuotes),
                        self::Open_Delimiter => 1, //变量
                        self::DoubleQuotesString => 1,
                    ),
                    'open' => self::In_DoubleQuotes,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="双引号关闭">
                self::Close_DoubleQuotes => array(
                    'rule' => '"',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::DoubleArrow => 0,
                        self::Symbol => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                    'close' => self::In_DoubleQuotes,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="引号内分界符开">
                self::Open_Delimiter => array(
                    'rule' => '`',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Parentheses => 0,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                    'open' => self::In_Delimiter,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="引号内分界符闭">
                self::Close_Delimiter => array(
                    'rule' => '`',
                    'token' => 'syh_string',
                    'next' => array(
                        self::Close_DoubleQuotes => array(1, self::In_DoubleQuotes),
                        self::DoubleQuotesString => 1, //变量
                    ),
                    'close' => self::In_Delimiter,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="属性开始">
                self::Open_TagAttr => array(
                    'rule' => '\@?\w+=',
                    'token' => 'attr',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Array => 0,
                        self::Open_Parentheses => 0,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                    'open' => self::In_TagAttr,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="单属性">
                self::Single_TagAttr => array(
                    'rule' => '\w+',
                    'token' => 'attr',
                    'next' => array(
                        self::Open_TagAttr => 6,
                        self::Single_TagAttr => 6,
                        self::Close_Tpl => 0,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="属性结束">
                self::Close_TagAttr => array(
                    'rule' => '\s+',
                    'token' => 'empty',
                    'next' => array(
                        self::Open_TagAttr => 6,
                        self::Single_TagAttr => 6,
                        self::Close_Tpl => array(0, self::In_Tpl),
                    ),
                    'close' => self::In_TagAttr,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="数值">
                self::Number => array(
                    'rule' => '[-]?(?:\d+\.\d+|\d+|\.\d+)',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::DoubleArrow => 0,
                        self::Symbol => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="常量">
                self::Constant => array(
                    'rule' => '\w+::\w+|\w+',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::DoubleArrow => 0,
                        self::Symbol => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="变量">
                self::Variable => array(
                    'rule' => '\$\w+',
                    'token' => 'var',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::DoubleArrow => 0,
                        self::ArrowFuncOpen => 1,
                        self::VariableArrow => 1,
                        self::VariablePoint => 1,
                        self::Symbol => 0,
                        self::Assign => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="单引号内字符">
                self::SingleQuotesString => array(
                    'rule' => '[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*',
                    'next' => array(
                        self::Close_SingleQuotes => 1,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="双引号内字符">
                self::DoubleQuotesString => array(
                    'rule' => '[^"`\\\\]*(?:\\\\.[^"`\\\\]*)*',
                    'next' => array(
                        self::Open_Delimiter => 1,
                        self::Close_DoubleQuotes => array(1, self::In_DoubleQuotes),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="静态变量">
                self::StaticVariable => array(
                    'rule' => '\w+::\$\w+',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::DoubleArrow => 0,
                        self::ArrowFuncOpen => 1,
                        self::VariableArrow => 1,
                        self::VariablePoint => 1,
                        self::Symbol => 0,
                        self::Assign => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="变量属性">
                self::VariableAttr => array(
                    'rule' => '\@\w+',
                    'token' => 'code',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::Symbol => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="修饰函数">
                self::ModifiersFunc => array(
                    'rule' => '\|\w+',
                    'next' => array(
                        self::Colons => 0,
                        self::ModifiersFunc => 0,
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                    ),
                    'open' => self::In_ModifiersFunc,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="代码关键字">
                self::KeyWord => array(
                    'rule' => '\s+as\s|\s+to\s',
                    'token' => 'code',
                    'next' => array(
                        self::Variable => 0,
                        self::Number => 0,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="点键名">
                self::VariablePoint => array(
                    'rule' => '\.\w+',
                    'token' => 'varkey',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::DoubleArrow => 0,
                        self::ArrowFuncOpen => 1,
                        self::VariableArrow => 1,
                        self::VariablePoint => 1,
                        self::Symbol => 0,
                        self::Assign => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="箭头键名">
                self::VariableArrow => array(
                    'rule' => '->\w+',
                    'token' => 'varkey',
                    'next' => array(
                        self::Close_Tpl => array(0,
                            array(
                                self::In_ModifiersFunc,
                                self::In_TagAttr,
                                self::In_ExtendTag,
                                self::In_Tag,
                                self::In_Tpl
                            )
                        ),
                        self::Close_Parentheses => array(0, self::In_Parentheses), //如果存在嵌套的小括号
                        self::Close_Brackets => array(0, self::In_Brackets), //如果是在键名之内
                        self::Close_Array => array(0, self::In_Array), //如果是在数组之内
                        self::Open_Brackets => 1, //如果是在键名之内
                        self::Close_Func => array(0, self::In_Func), //函数内
                        self::Close_StaticFunc => array(0, self::In_StaticFunc), //静态函数内
                        self::Comma => array(0, array(self::In_Array, self::In_Func, self::In_StaticFunc)), //数组内
                        self::DoubleArrow => 0,
                        self::ArrowFuncOpen => 1,
                        self::VariableArrow => 1,
                        self::VariablePoint => 1,
                        self::Symbol => 0,
                        self::Assign => 0,
                        self::ModifiersFunc => 0,
                        self::Colons => array(0, self::In_ModifiersFunc),
                        self::Close_Delimiter => array(0, self::In_Delimiter), //必须是在字符串内
                        self::Close_TagAttr => array(1, self::In_TagAttr),
                        self::KeyWord => array(1, self::In_Tag),
                        self::Semicolon => array(0, self::In_Tag),
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="箭头函数">
                self::ArrowFuncOpen => array(
                    'rule' => '->\w+\(',
                    'token' => 'code',
                    'next' => array(
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_StaticFunc => 0,
                        self::Open_Func => 0,
                        self::StaticVariable => 0,
                        self::Open_Parentheses => 0,
                        self::Open_Array => 0,
                        self::Close_Func => array(0, self::In_Func),
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0, //前面必须带空格
                    ),
                    'open' => self::In_Func,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="打开模板">
                self::Open_Tpl => array(
                    'rule' => preg_quote(RulesManager::$left_delimiter, '@'),
                    'next' => array(
                        self::Open_ExtendTag => 1,
                        self::Open_EndTag => 1,
                        self:: Open_SimpleTag => 7,
                        self::Open_SpecialTag => 1,
                        self::Open_StaticFunc => 0,
                        self::StaticVariable => 0,
                        self::Open_Func => 0,
                        self::Open_Tag => 1,
                        self::Number => 0,
                        self::Variable => 0, //变量
                        self::Open_SingleQuotes => 0,
                        self::Open_DoubleQuotes => 0,
                        self::Open_Parentheses => 0,
                        self::Constant => 0,
                        self::Not => 0,
                        self::PrefixIncDec => 0,
                    ),
                    'open' => self::In_Tpl,
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="结束模板">
                self::Close_Tpl => array(
                    'rule' => preg_quote(RulesManager::$right_delimiter, '@'),
                    'token' => 'closetpl',
                    'next' => array(
                        self::Finish => 1,
                    ),
                    'close' => array(
                        self::In_TagAttr,
                        self::In_ModifiersFunc,
                        self::In_Tag,
                        self::In_ExtendTag,
                        self::In_Tpl
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="初始化">
                self::Init => array(
                    'next' => array(
                        self::Open_Tpl => 1,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="打开配置项">
                self::Init_Config => array(
                    'next' => array(
                        self::Open_Config => 1,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="初始配置项">
                self::Open_Config => array(
                    'rule' => preg_quote(RulesManager::$left_delimiter . '#', '@'),
                    'next' => array(
                        self::ConfigKey => 1,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="打开配置项">
                self::ConfigKey => array(
                    'rule' => '\w+(\.\w+)*',
                    'token' => 'cfgkey',
                    'next' => array(
                        self::Close_Config => 1,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="打开配置项">
                self::Close_Config => array(
                    'rule' => preg_quote('#' . RulesManager::$right_delimiter, '@'),
                    'next' => array(
                        self::Finish => 1,
                    ),
                ),
                //</editor-fold>
                //<editor-fold defaultstate="collapsed" desc="打开配置项">
                self::Init_Literal => array(
                    'next' => array(
                        self::Finish => 1,
                    ),
                ),
                    //</editor-fold>
            ];
        }

        public static function getRule($tag) {
            return self::$names[$tag]['rule'];
        }

        public static function getToken($tag) {
            return isset(self::$names[$tag]['token']) ? self::$names[$tag]['token'] : '';
        }

        public static function getNext($tag) {
            return isset(self::$names[$tag]['next']) ? self::$names[$tag]['next'] : NULL;
        }

        public static function getNextTow($tag) {
            return self::$names[$tag]['next2'];
        }

        public static function haveNextTow($tag) {
            return isset(self::$names[$tag]['next2']);
        }

        public static function getClose($tag) {
            $close = isset(self::$names[$tag]['close']) ? self::$names[$tag]['close'] : NULL;
            if ($close == NULL) {
                return NULL;
            }
            if (!is_array($close)) {
                $close = [$close];
            }
            return $close;
        }

        public static function getOpen($tag) {
            return isset(self::$names[$tag]['open']) ? self::$names[$tag]['open'] : NULL;
        }

        public static function reset($left_delimiter, $right_delimiter) {
            if (self::$left_delimiter != $left_delimiter || self::$right_delimiter != $right_delimiter) {
                self::$left_delimiter = $left_delimiter;
                self::$right_delimiter = $right_delimiter;
                self::$names[self::Open_Tpl]['rule'] = preg_quote(RulesManager::$left_delimiter, '@');
                self::$names[self::Close_Tpl]['rule'] = preg_quote(RulesManager::$right_delimiter, '@');
                self::$names[self::Open_Config]['rule'] = preg_quote(RulesManager::$left_delimiter . '#', '@');
                self::$names[self::Close_Config]['rule'] = preg_quote('#' . RulesManager::$right_delimiter, '@');
                self::$error_name[self::In_Tpl] = '没有找到模板结束符号 ' . RulesManager::$right_delimiter . ' .';
                self::$error_name[self::In_Brock] = '没有找到' . RulesManager::$left_delimiter . '/block' . RulesManager::$right_delimiter . '模板块结束符号 .';
            }
        }

    }

}
