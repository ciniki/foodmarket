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
    };
    this.menu.open = function(cb) {
        this.refresh();
        this.show(cb);
    }
    this.menu.keywordsUpdate = function() {
        M.api.getJSONCb('ciniki.foodmarket.keywordsUpdate', {'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            alert("Keywords updated.");
        });
    }
    this.menu.addClose('Back');

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
