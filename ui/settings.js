//
function ciniki_foodmarket_settings() {
    //
    // The menu panel
    //
    this.menu = new M.panel('Settings', 'ciniki_foodmarket_settings', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.foodmarket.settings.menu');
    this.menu.sections = {
        'search':{'label':'', 'list':{
            'reindex':{'label':'Update Search Index', 'fn':'M.ciniki_foodmarket_settings.menu.keywordsUpdate();'},
            }},
        '_seasons':{'label':'', 
            'visible':function() {return M.modFlagSet('ciniki.foodmarket', 0x01);},
            'list':{
                'seasons':{'label':'Seasons', 'fn':'M.ciniki_foodmarket_settings.seasons.open(\'M.ciniki_foodmarket_settings.menu.open();\');'},
            }},
    };
    this.menu.open = function(cb) {
        this.refresh();
        this.show(cb);
    }
    this.menu.keywordsUpdate = function() {
        M.api.getJSONCb('ciniki.foodmarket.keywordsUpdate', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            alert("Keywords updated.");
        });
    }
    this.menu.addClose('Back');

    //
    // The seasons list
    //
    this.seasons = new M.panel('season', 'ciniki_foodmarket_settings', 'seasons', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.settings.seasons');
    this.seasons.data = {};
    this.seasons.nplist = [];
    this.seasons.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search season',
            'noData':'No season found',
            },
        'seasons':{'label':'Season', 'type':'simplegrid', 'num_cols':1,
            'noData':'No season',
            'addTxt':'Add Season',
            'addFn':'M.ciniki_foodmarket_settings.season.open(\'M.ciniki_foodmarket_settings.seasons.open();\',0,null);'
            },
    }
    this.seasons.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.seasonSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_foodmarket_settings.seasons.liveSearchShow('search',null,M.gE(M.ciniki_foodmarket_settings.seasons.panelUID + '_' + s), rsp.seasons);
                });
        }
    }
    this.seasons.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.seasons.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_foodmarket_settings.season.open(\'M.ciniki_foodmarket_settings.seasons.open();\',\'' + d.id + '\');';
    }
    this.seasons.cellValue = function(s, i, j, d) {
        if( s == 'seasons' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
    }
    this.seasons.rowFn = function(s, i, d) {
        if( s == 'seasons' ) {
            return 'M.ciniki_foodmarket_settings.season.open(\'M.ciniki_foodmarket_settings.seasons.open();\',\'' + d.id + '\',M.ciniki_foodmarket_settings.season.nplist);';
        }
    }
    this.seasons.open = function(cb) {
        M.api.getJSONCb('ciniki.foodmarket.seasonList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_settings.seasons;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.seasons.addClose('Back');

    //
    // The panel to edit Season
    //
    this.season = new M.panel('Season', 'ciniki_foodmarket_settings', 'season', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.settings.season');
    this.season.data = null;
    this.season.season_id = 0;
    this.season.nplist = [];
    this.season.sections = {
        'general':{'label':'Season', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'start_date':{'label':'Start Date', 'required':'yes', 'type':'date'},
            'end_date':{'label':'End Date', 'required':'yes', 'type':'date'},
            'csa_start_date':{'label':'CSA Start Date', 'required':'yes', 'type':'date'},
            'csa_end_date':{'label':'CSA End Date', 'required':'yes', 'type':'date'},
            'csa_days':{'label':'CSA Days', 'required':'yes', 'type':'flags', 'flags':{
//                '1':{'name':'Sun'},
                '2':{'name':'Mon'},
                '3':{'name':'Tue'},
                '4':{'name':'Wed'},
                '5':{'name':'Thu'},
                '6':{'name':'Fri'},
                '7':{'name':'Sat'},
                '8':{'name':'Sun'},
                }},
            }},
        '_add':{'label':'Add Order Dates', 
            'active':function(){return M.ciniki_foodmarket_settings.season.season_id == 0 ? 'yes' : 'no'},
            'fields':{
                'orders_start_date':{'label':'Start Date', 'type':'date'},
                'orders_end_date':{'label':'End Date', 'type':'date'},
                'orders_days':{'label':'Order Days', 'type':'flags', 'flags':{
    //                '1':{'name':'Sun'},
                    '2':{'name':'Mon'},
                    '3':{'name':'Tue'},
                    '4':{'name':'Wed'},
                    '5':{'name':'Thu'},
                    '6':{'name':'Fri'},
                    '7':{'name':'Sat'},
                    '8':{'name':'Sun'},
                    }},
            }},
        'products':{'label':'Products', 'type':'simplegrid', 'num_cols':4,
            'active':function(){return M.ciniki_foodmarket_settings.season.season_id > 0 ? 'yes' : 'no'},
            'headerValues':['Name', 'Frequency', 'Weeks', 'Price'],
            'addTxt':'Add Product',
            'addFn':'M.ciniki_foodmarket_settings.season.save("M.ciniki_foodmarket_settings.sproduct.open(\'M.ciniki_foodmarket_settings.season.open();\',0,M.ciniki_foodmarket_settings.season.season_id);");',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_settings.season.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_foodmarket_settings.season.season_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_foodmarket_settings.season.remove();'},
            }},
        };
    this.season.fieldValue = function(s, i, d) { return this.data[i]; }
    this.season.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.foodmarket.seasonHistory', 'args':{'tnid':M.curTenantID, 'season_id':this.season_id, 'field':i}};
    }
    this.season.cellValue = function(s, i, j, d) {
        if( s == 'products' ) {
            switch(j) {
                case 0: return d.pio_name;
                case 1: return d.repeat_days;
                case 2: return d.repeat_weeks;
                case 3: return d.price_display;
            }
        }
    }
    this.season.rowFn = function(s, i, d) {
        if( s == 'products' ) {
            return 'M.ciniki_foodmarket_settings.season.save("M.ciniki_foodmarket_settings.sproduct.open(\'M.ciniki_foodmarket_settings.season.open();\',\'' + d.id + '\',0);");';
        }
    }
    this.season.open = function(cb, sid) {
        if( sid != null ) { this.season_id = sid; }
        M.api.getJSONCb('ciniki.foodmarket.seasonGet', {'tnid':M.curTenantID, 'season_id':this.season_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_settings.season;
            p.data = rsp.season;
            if( rsp.season.id > 0 ) {
                p.size = 'medium mediumaside';
            } else {
                p.size = 'medium';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.season.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_settings.season.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.season_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.foodmarket.seasonUpdate', {'tnid':M.curTenantID, 'season_id':this.season_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.foodmarket.seasonAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_settings.season.season_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.season.remove = function() {
        if( confirm('Are you sure you want to remove season?') ) {
            M.api.getJSONCb('ciniki.foodmarket.seasonDelete', {'tnid':M.curTenantID, 'season_id':this.season_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_settings.season.close();
            });
        }
    }
    this.season.addButton('save', 'Save', 'M.ciniki_foodmarket_settings.season.save();');
    this.season.addClose('Cancel');

    //
    // The panel to edit Season Product
    //
    this.sproduct = new M.panel('Season Product', 'ciniki_foodmarket_settings', 'sproduct', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.settings.sproduct');
    this.sproduct.data = null;
    this.sproduct.sp_id = 0;
    this.sproduct.nplist = [];
    this.sproduct.sections = {
        'general':{'label':'', 'fields':{
            'output_id':{'label':'Product', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'pio_name'}, 'options':{}},
            'repeat_days':{'label':'Repeat Days', 'required':'yes', 'type':'toggle', 'toggles':{'7':'Weekly', '14':'Bi-Weekly'}},
            'repeat_weeks':{'label':'Repeat Weeks', 'required':'yes', 'type':'text', 'size':'small'},
            'price':{'label':'Price', 'required':'yes', 'type':'text', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_settings.sproduct.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_foodmarket_settings.sproduct.sp_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_foodmarket_settings.sproduct.remove();'},
            }},
        };
    this.sproduct.fieldValue = function(s, i, d) { return this.data[i]; }
    this.sproduct.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.foodmarket.seasonProductHistory', 'args':{'tnid':M.curTenantID, 'sp_id':this.sp_id, 'field':i}};
    }
    this.sproduct.open = function(cb, spid, season_id) {
        if( spid != null ) { this.sp_id = spid; }
        if( season_id != null ) { this.season_id = season_id; }
        M.api.getJSONCb('ciniki.foodmarket.seasonProductGet', {'tnid':M.curTenantID, 'sp_id':this.sp_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_settings.sproduct;
            p.data = rsp.product;
            console.log(rsp);
            p.sections.general.fields.output_id.options = rsp.outputs;
            p.refresh();
            p.show(cb);
        });
    }
    this.sproduct.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_settings.sproduct.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.sp_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.foodmarket.seasonProductUpdate', {'tnid':M.curTenantID, 'sp_id':this.sp_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.foodmarket.seasonProductAdd', {'tnid':M.curTenantID, 'season_id':this.season_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_settings.sproduct.sp_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.sproduct.remove = function() {
        if( confirm('Are you sure you want to remove seasons product?') ) {
            M.api.getJSONCb('ciniki.foodmarket.seasonProductDelete', {'tnid':M.curTenantID, 'sp_id':this.sp_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_settings.sproduct.close();
            });
        }
    }
    this.sproduct.addButton('save', 'Save', 'M.ciniki_foodmarket_settings.sproduct.save();');
    this.sproduct.addClose('Cancel');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_foodmarket_settings', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 
    
        this.menu.open(cb);
    }
}
