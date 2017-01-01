<?php defined('iPHP') OR exit('What are you doing?');?>
[{
    "id": "tools",
    "children": [{
        "caption": "-"
    },{
        "id": "cache",
        "caption": "清理缓存",
        "icon": "refresh",
        "children": [{
            "caption": "更新所有缓存",
            "href": "cache&do=all",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "-"
        }, {
            "caption": "更新系统设置",
            "href": "cache&acp=admincp.config.app",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "更新菜单缓存",
            "href": "cache&do=menu",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "清除模板缓存",
            "href": "cache&do=tpl",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "-"
        }, {
            "caption": "更新所有分类缓存",
            "href": "cache&do=allcategory",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "更新文章栏目缓存",
            "href": "cache&do=category",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "更新推送版块缓存",
            "href": "cache&do=pushcategory",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "更新标签分类缓存",
            "href": "cache&do=tagcategory",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "更新属性缓存",
            "href": "cache&acp=propAdmincp",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "更新内链缓存",
            "href": "cache&acp=keywordsAdmincp",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "更新过滤缓存",
            "href": "cache&acp=filterAdmincp",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }, {
            "caption": "-"
        }, {
            "caption": "重计栏目文章数",
            "href": "cache&do=article_count",
            "icon": "refresh",
            "target": "iPHP_FRAME"
        }]
    }]
}]
