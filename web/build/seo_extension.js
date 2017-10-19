var $ = require('jquery');
var Backbone = require('backbone');

SeoExtension = Backbone.Model.extend({

    initialize: function (titlepostfix, titlefield, descriptionfield, hostname, key) {

        this.titlepostfix = titlepostfix;
        this.titlefield = titlefield;
        this.descriptionfield = descriptionfield;
        this.hostname = hostname;
        this.key = "#" + key;
        this.update();

        $('#seofields-title, #seofields-description').on('keyup input paste', function(){
            seoExtension.update();
        })

    },

    update: function() {

        if ($('#seofields-title').val() != "") {
            var title = $('#seofields-title').val() + this.titlepostfix;
        } else {
            var title = $('#' + this.titlefield).val() + this.titlepostfix;
        }

        if ($('#seofields-description').val() != "") {
            var description = $('#seofields-description').val();
        } else {
            var description = $('#' + this.descriptionfield).val();
        }

        var link = $('#slug').text();
        var shortlink = $('#seofields-shortlink').val();
        var canonical = $('#seofields-canonical').val();
        var robots = $('#seofields-robots').val();

        $('#seosnippet .title').text( this.trimtext(title, 70) );
        $('#seosnippet cite').text( this.hostname + link );
        $('#seosnippet .excerpt').text( this.trimtext(description, 156) );

        var value = {
            'title': $('#seofields-title').val(),
            'description': $('#seofields-description').val(),
            'shortlink': $('#seofields-shortlink').val(),
            'canonical': $('#seofields-canonical').val(),
            'robots': $('#seofields-robots').val(),
            'ogtype': $('#seofields-ogtype').val(),
            'keywords': $('#seofields-keywords').val() }

        $(this.key).val( JSON.stringify(value) );

        window.clearTimeout(this.timer);
        this.timer = window.setTimeout(function(){ seoExtension.update(); }, 3000);

    },

    strip: function(html) {
        var tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    },

    trimtext: function(str, length) {
        str = this.strip(str);
        return str.length > length ? str.substring(0, length - 1) + '…' : str;
    }

});
