var internPlugin = ActiveBuild.UiPlugin.extend({
    id: 'build-intern-errors',
    css: 'col-lg-12 col-md-12 col-sm-12 col-xs-12',
    title: 'intern',
    lastData: null,
    lastMeta: null,
    displayOnUpdate: false,
    box: true,
    rendered: false,

    register: function() {
        var self = this;
        var query_data = ActiveBuild.registerQuery('intern-summary', -1, {key: 'intern-summary'});
        var query_meta_data = ActiveBuild.registerQuery('intern-meta', -1, {key: 'intern-meta'});


        $(window).on('intern-summary', function(data) {
            self.onUpdateSummary(data);
        });

        $(window).on('intern-meta', function(data) {
            self.onUpdateMeta(data);
        });

        $(window).on('build-updated', function() {
            if (!self.rendered) {
                self.displayOnUpdate = true;
                query_data();
                query_meta_data();
            }
        });
    },

    render: function() {

        return $('<table class="table" id="intern-summary">' +
            '<thead>' +
            '<tr><th>Platform</th><th>Failures</th><th>Tests</th><th>Time</th></tr>' +
            '</thead><tbody></tbody><tfoot></tfoot></table>');
    },

    onUpdateSummary: function(e) {
        if (!e.queryData) {
            $('#build-intern-errors').hide();
            return;
        }

        this.rendered = true;
        this.lastData = e.queryData;

        var tests = this.lastData[0].meta_value;
        var tbody = $('#intern-summary tbody');
        tbody.empty();
        var counter = 0;
       
        for (var i in tests) {

            var rows = '<tr data-toggle="collapse" data-target="#collapse-intern'+counter+'">' +
                '<td>'+tests[i].name+'</td>' +
                '<td>'+tests[i].failures+'</td>' +
                '<td>'+tests[i].tests+'</td>' +
                '<td>'+tests[i].time+'</td>'+
                '</tr><tr id="collapse-intern'+counter+'" class="collapse" ><td colspan="4"><table>';

            var errors = tests[i].errors;

            for (var k in errors) {
                var details =       '<tr>' +                            
                                    '<td style="padding: 10px; white-space: nowrap">'+errors[k].suiteName+'</td>' +
                                    '<td style="padding: 10px; white-space: nowrap">'+errors[k].testName+'</td>' +
                                    '<td style="font-size: 10px; padding: 10px">'+errors[k].message+'</td></tr>';    

                rows += details;
            }

            rows += '</table></td></tr>'
            
            tbody.append(rows);
            counter++;
        }

        $('#build-intern-errors').show();
    },

    onUpdateMeta: function(e) {
        if (!e.queryData) {
            return;
        }
        this.lastMeta = e.queryData;

        var data = this.lastMeta[0].meta_value;
        var tfoot = $('#intern-summary tfoot');
        tfoot.empty();  

        var row = '<tr>' +
            '<td colspan="4">' +
            '<strong>'+data.tests+'</strong> testów wykonano w czase ' +
            '<strong>'+data.timetaken+'</strong> sekund. ' +
            '<strong>'+data.failure+'</strong> błędów.' +
            '</td>' +
            '</tr>';

        tfoot.append(row);
        
    }
});

ActiveBuild.registerPlugin(new internPlugin());