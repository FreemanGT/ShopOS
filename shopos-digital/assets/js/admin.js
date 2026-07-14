(function($){
'use strict';
$(function(){
var N=fdVars.nonce, A=fdVars.ajax;

// -------------------------------------------------------------------
// Reusable backup-recency confirmation modal (Wave 4b / E-4).
// Replaces bare confirm() for every destructive action so the user
// must tick "I have a recent backup" before the action runs.
// -------------------------------------------------------------------
function fdConfirm(opts, onOk){
    // opts: { title, body, okLabel, okClass, needsBackup }
    var existing = $('#shopos-digital-confirm-modal');
    if (existing.length) existing.remove();

    var okLabel = opts.okLabel || 'Continue';
    var okClass = opts.okClass || 'button-primary';
    var html = [
        '<div id="shopos-digital-confirm-modal" class="shopos-digital-modal">',
          '<div class="shopos-digital-modal-overlay shopos-digital-confirm-overlay"></div>',
          '<div class="shopos-digital-modal-content" style="max-width:520px">',
            '<span class="shopos-digital-modal-close shopos-digital-confirm-close">&times;</span>',
            '<h3 style="margin-top:0">', opts.title || 'Confirm', '</h3>',
            '<div class="shopos-digital-confirm-body">', opts.body || '', '</div>',
            (opts.needsBackup !== false ? (
                '<label style="display:block;margin:18px 0;padding:10px 12px;background:#fff3cd;border:1px solid #ffe69c;border-radius:4px">' +
                '<input type="checkbox" id="shopos-digital-confirm-backup"> <strong>I have a recent backup of this site.</strong>' +
                '</label>'
            ) : ''),
            '<p style="text-align:right;margin-bottom:0">',
              '<button type="button" class="button shopos-digital-confirm-cancel">Cancel</button> ',
              '<button type="button" class="button ', okClass, '" id="shopos-digital-confirm-ok"', (opts.needsBackup !== false ? ' disabled' : ''), '>', okLabel, '</button>',
            '</p>',
          '</div>',
        '</div>'
    ].join('');

    $('body').append(html);
    var $m = $('#shopos-digital-confirm-modal').show();
    $m.find('#shopos-digital-confirm-backup').on('change', function(){
        $m.find('#shopos-digital-confirm-ok').prop('disabled', !this.checked);
    });
    $m.find('.shopos-digital-confirm-cancel, .shopos-digital-confirm-overlay, .shopos-digital-confirm-close').on('click', function(){ $m.remove(); });
    $m.find('#shopos-digital-confirm-ok').on('click', function(){ $m.remove(); onOk(); });
}

// Deep Reindex - Tier 1
$('#shopos-digital-deep-all').on('change',function(){$('.shopos-digital-deep-cb').prop('checked',this.checked)});
$('#shopos-digital-deep-apply').on('click',function(){
    var b=$(this),s=[];
    $('.shopos-digital-deep-cb:checked').each(function(){s.push($(this).val())});
    if(!s.length){$('#shopos-digital-deep-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Select at least one table.</div>');return}
    fdConfirm({
        title: 'Apply Deep Reindex',
        body: '<p>This will restructure PRIMARY KEYs on <strong>' + s.length + '</strong> table(s). Safe and reversible, but may take up to 60 seconds per large table. Your site may enter maintenance mode briefly.</p>',
        okLabel: '⚡ Apply Deep Reindex'
    }, function(){
        b.prop('disabled',true).text('⏳ Reindexing...');
        $('#shopos-digital-deep-msg').html('<div class="shopos-digital-alert shopos-digital-info">Restructuring PRIMARY KEYs... large tables may take a minute.</div>');
        $.post(A,{action:'shopos_digital_deep_reindex',nonce:N,tables:s},function(r){
            b.prop('disabled',false).text('⚡ Apply Deep Reindex');
            if(r.success){
                var h='<div class="shopos-digital-alert shopos-digital-ok"><strong>Deep reindex complete!</strong><br>';
                for(var k in r.data) h+=k+': <strong>'+r.data[k]+'</strong><br>';
                h+='</div>';$('#shopos-digital-deep-msg').html(h);
                setTimeout(function(){location.reload()},2000);
            }else{$('#shopos-digital-deep-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Error. Check error log.</div>')}
        }).fail(function(){b.prop('disabled',false).text('⚡ Apply Deep Reindex');$('#shopos-digital-deep-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Timeout. Table may still be processing. Refresh in 60s.</div>')});
    });
});
$('#shopos-digital-deep-revert').on('click',function(){
    var b=$(this),s=[];
    $('.shopos-digital-deep-cb:checked').each(function(){s.push($(this).val())});
    if(!s.length){$('#shopos-digital-deep-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Select tables to revert.</div>');return}
    fdConfirm({
        title: 'Revert Deep Reindex',
        body: '<p>Revert <strong>' + s.length + '</strong> table(s) to WordPress standard indexes? Existing data is preserved; only the PRIMARY KEY structure changes back.</p>',
        okLabel: '↩️ Revert'
    }, function(){
        b.prop('disabled',true).text('⏳ Reverting...');
        $.post(A,{action:'shopos_digital_deep_revert',nonce:N,tables:s},function(r){
            b.prop('disabled',false).text('↩️ Revert to WordPress Standard');
            if(r.success){
                var h='<div class="shopos-digital-alert shopos-digital-ok"><strong>Reverted!</strong><br>';
                for(var k in r.data) h+=k+': <strong>'+r.data[k]+'</strong><br>';
                h+='</div>';$('#shopos-digital-deep-msg').html(h);
                setTimeout(function(){location.reload()},2000);
            }
        });
    });
});

// Index management - Tier 2
$('#shopos-digital-idx-all').on('change',function(){$('.shopos-digital-idx-cb').prop('checked',this.checked)});
$('#shopos-digital-create-idx').on('click',function(){
    var b=$(this),s=[];
    $('.shopos-digital-idx-cb:checked').each(function(){s.push($(this).val())});
    b.prop('disabled',true).text('⏳ Working...');
    $('#shopos-digital-idx-msg').html('<div class="shopos-digital-alert shopos-digital-info">Creating indexes... may take a moment on large tables.</div>');
    $.post(A,{action:'shopos_digital_create_indexes',nonce:N,indexes:s},function(r){
        b.prop('disabled',false).text('✅ Update Indexes');
        if(r.success) $('#shopos-digital-idx-msg').html('<div class="shopos-digital-alert shopos-digital-ok">Done! Created: '+r.data.created+', Dropped: '+r.data.dropped+'</div>');
        else $('#shopos-digital-idx-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Error. Retry.</div>');
    }).fail(function(){b.prop('disabled',false).text('✅ Update Indexes');$('#shopos-digital-idx-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Timeout. Refresh and retry.</div>')});
});
$('#shopos-digital-drop-idx').on('click',function(){
    fdConfirm({
        title: 'Drop All Secondary Indexes',
        body: '<p>Drop <strong>ALL</strong> ShopOS Digital secondary indexes? You can re-create them at any time from this tab.</p>',
        okLabel: '❌ Drop All'
    }, function(){
        var b=$('#shopos-digital-drop-idx');b.prop('disabled',true);
        $.post(A,{action:'shopos_digital_drop_indexes',nonce:N},function(r){
            b.prop('disabled',false);
            if(r.success){$('.shopos-digital-idx-cb').prop('checked',false);$('#shopos-digital-idx-msg').html('<div class="shopos-digital-alert shopos-digital-info">'+r.data.message+'</div>')}
        });
    });
});

// Cleanup
$('#shopos-digital-run-cleanup').on('click',function(){
    var b=$(this);b.prop('disabled',true).text('⏳ Running...');
    $('#shopos-digital-cleanup-msg').html('<div class="shopos-digital-alert shopos-digital-info">Running cleanup...</div>');
    $.post(A,{action:'shopos_digital_run_cleanup',nonce:N},function(r){
        b.prop('disabled',false).text('🧹 Run Cleanup Now');
        if(r.success){
            var h='<div class="shopos-digital-alert shopos-digital-ok"><strong>Cleanup complete!</strong><br>';
            for(var k in r.data) h+=k.replace(/_/g,' ')+': <strong>'+r.data[k]+'</strong> items<br>';
            h+='</div>';$('#shopos-digital-cleanup-msg').html(h);
        }
    }).fail(function(){b.prop('disabled',false).text('🧹 Run Cleanup Now');$('#shopos-digital-cleanup-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Timeout. Retry.</div>')});
});

// Optimize Tables Now (requires backup confirmation — locks tables)
$('#shopos-digital-optimize-tables').on('click',function(){
    fdConfirm({
        title: 'Optimize Database Tables',
        body: '<p><strong>Warning:</strong> OPTIMIZE TABLE rebuilds every WooCommerce and WordPress table. On large stores this <strong>locks tables for 1–15 minutes</strong>. Run during a maintenance window only.</p>',
        okLabel: '⚡ Optimize Now',
        okClass: 'button-primary'
    }, function(){
        var b=$('#shopos-digital-optimize-tables');
        b.prop('disabled',true).text('⏳ Optimizing...');
        $('#shopos-digital-optimize-msg').html('<div class="shopos-digital-alert shopos-digital-info">Running OPTIMIZE TABLE... this may take a while.</div>');
        $.post(A,{action:'shopos_digital_optimize_tables',nonce:N},function(r){
            b.prop('disabled',false).text('⚡ Optimize Tables Now');
            if(r.success){
                var h='<div class="shopos-digital-alert shopos-digital-ok"><strong>Optimize complete!</strong><br>';
                if(r.data.optimized) h+='Tables optimized: <strong>'+r.data.optimized+'</strong><br>';
                if(r.data.errors && r.data.errors.length) h+='Errors: <strong>'+r.data.errors.length+'</strong> (see error log)<br>';
                h+='</div>';$('#shopos-digital-optimize-msg').html(h);
            } else {
                var msg = (r.data && r.data.message) ? r.data.message : 'Operation failed.';
                $('#shopos-digital-optimize-msg').html('<div class="shopos-digital-alert shopos-digital-warn">'+msg+'</div>');
            }
        }).fail(function(){
            b.prop('disabled',false).text('⚡ Optimize Tables Now');
            $('#shopos-digital-optimize-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Timeout. Operation may still be running. Refresh in a few minutes.</div>');
        });
    });
});

// Autoload audit
$('#shopos-digital-audit-autoload').on('click',function(){
    var b=$(this);b.prop('disabled',true).text('⏳ Scanning...');
    $.post(A,{action:'shopos_digital_audit_autoload',nonce:N},function(r){
        b.prop('disabled',false).text('🔍 Audit Top 30 Largest Autoloaded Options');
        if(r.success&&r.data.length){
            var h='<table class="shopos-digital-autoload-table"><thead><tr><th>Option Name</th><th>Size (KB)</th><th>Risk</th></tr></thead><tbody>';
            r.data.forEach(function(row){
                var risk=parseFloat(row.size_kb)>100?'🔴 Large':parseFloat(row.size_kb)>10?'🟡 Medium':'🟢 OK';
                h+='<tr><td><code>'+row.option_name+'</code></td><td>'+row.size_kb+' KB</td><td>'+risk+'</td></tr>';
            });
            h+='</tbody></table>';$('#shopos-digital-autoload-results').html(h);
        }
    });
});

// Autoload fix (backup-confirmed)
$('#shopos-digital-fix-autoload').on('click',function(){
    fdConfirm({
        title: 'Run Autoload Auto-Fix',
        body: '<p>This sets <code>autoload=no</code> for options larger than your configured threshold. Critical WP options are protected. Changes are reversible (re-run with a larger threshold to re-enable).</p>',
        okLabel: '⚡ Run Auto-Fix'
    }, function(){
        var b=$('#shopos-digital-fix-autoload');b.prop('disabled',true);
        $.post(A,{action:'shopos_digital_fix_autoload',nonce:N},function(r){
            b.prop('disabled',false);
            if(r.success) $('#shopos-digital-fix-autoload-msg').html('<div class="shopos-digital-alert shopos-digital-ok">Fixed '+r.data.fixed+' options. Autoload reduced.</div>');
        });
    });
});

// MyISAM convert (backup-confirmed — writes ALTER TABLE)
$('#shopos-digital-convert-myisam').on('click',function(){
    fdConfirm({
        title: 'Convert MyISAM → InnoDB',
        body: '<p>Converts every MyISAM table to InnoDB with <code>ALTER TABLE ... ENGINE=InnoDB</code>. Safe but can take several minutes on large tables. Irreversible without a backup — you cannot convert InnoDB back to MyISAM without manual SQL.</p>',
        okLabel: '🔄 Convert All'
    }, function(){
        var b=$('#shopos-digital-convert-myisam');b.prop('disabled',true).text('⏳ Converting...');
        $.post(A,{action:'shopos_digital_convert_myisam',nonce:N},function(r){
            b.prop('disabled',false).text('🔄 Convert All to InnoDB');
            if(r.success) $('#shopos-digital-myisam-msg').html('<div class="shopos-digital-alert shopos-digital-ok">Converted '+r.data.converted+' tables to InnoDB.</div>');
        });
    });
});

// Activity log — clear
$('#shopos-digital-clear-log').on('click',function(){
    fdConfirm({
        title: 'Clear Activity Log',
        body: '<p>This removes every entry from the ShopOS Digital activity log. No destructive database action is performed — only the audit trail is erased.</p>',
        okLabel: '🗑️ Clear Log',
        needsBackup: false
    }, function(){
        $.post(A,{action:'shopos_digital_clear_activity_log',nonce:N},function(r){
            if(r.success){
                $('#shopos-digital-clear-log-msg').html('<div class="shopos-digital-alert shopos-digital-ok">'+r.data.message+'</div>');
                setTimeout(function(){location.reload()},900);
            }
        });
    });
});

// === PROFILER ===
$('#shopos-digital-prof-start').on('click',function(){
    var b=$(this),dur=$('#shopos-digital-prof-duration').val(),thr=$('#shopos-digital-prof-threshold').val();
    b.prop('disabled',true).text('⏳ Starting...');
    $.post(A,{action:'shopos_digital_profiler_start',nonce:N,duration:dur,threshold:thr},function(r){
        if(r.success){setTimeout(function(){location.reload()},500)}
        else{b.prop('disabled',false).text('▶ Start Profiler')}
    });
});
$('#shopos-digital-prof-stop').on('click',function(){
    var b=$(this);b.prop('disabled',true).text('⏳ Stopping...');
    $.post(A,{action:'shopos_digital_profiler_stop',nonce:N},function(){setTimeout(function(){location.reload()},500)});
});
$('#shopos-digital-prof-clear').on('click',function(){
    fdConfirm({
        title: 'Clear Captured Profiler Data',
        body: '<p>Wipe every captured slow query fingerprint? The profiler itself keeps running; only the accumulated samples are deleted.</p>',
        okLabel: '🗑️ Clear',
        needsBackup: false
    }, function(){
        $.post(A,{action:'shopos_digital_profiler_clear',nonce:N},function(){location.reload()});
    });
});
$('#shopos-digital-prof-refresh').on('click',function(){location.reload()});
$('#shopos-digital-prof-hide-design').on('change',function(){
    if(this.checked){
        $('#shopos-digital-prof-table-filtered').show();$('#shopos-digital-prof-table-all').hide();
    }else{
        $('#shopos-digital-prof-table-filtered').hide();$('#shopos-digital-prof-table-all').show();
    }
});
$(document).on('click','.shopos-digital-prof-explain',function(){
    var fp=$(this).data('fp'),btn=$(this);
    btn.prop('disabled',true).text('Running EXPLAIN...');
    $.post(A,{action:'shopos_digital_profiler_explain',nonce:N,fingerprint:fp},function(r){
        btn.prop('disabled',false).text('🔍 EXPLAIN');
        if(r.success&&r.data){
            var html='<h3>Query EXPLAIN Plan</h3>';
            if(r.data.error){
                html+='<p>'+r.data.error+'</p>';
            }else{
                html+='<pre style="background:#f5f5f5;padding:10px;overflow:auto;max-height:200px">'+r.data.query+'</pre>';
                html+='<table class="shopos-digital-prof-explain-tbl"><thead><tr>';
                if(r.data.explain&&r.data.explain.length){
                    for(var k in r.data.explain[0]) html+='<th>'+k+'</th>';
                    html+='</tr></thead><tbody>';
                    r.data.explain.forEach(function(row){
                        html+='<tr>';
                        for(var k in row){
                            var v=row[k]||'NULL';
                            var bg='';
                            if(k==='type'&&(v==='ALL'||v==='index')) bg='background:#ffcccc';
                            if(k==='Extra'&&v.indexOf('Using filesort')>=0) bg='background:#ffe0b2';
                            if(k==='Extra'&&v.indexOf('Using temporary')>=0) bg='background:#ffe0b2';
                            html+='<td style="'+bg+'">'+v+'</td>';
                        }
                        html+='</tr>';
                    });
                    html+='</tbody></table>';
                    html+='<p style="margin-top:10px"><strong>Key indicators:</strong><br>';
                    html+='<span style="background:#ffcccc;padding:2px 6px">type=ALL</span> = full table scan (BAD)<br>';
                    html+='<span style="background:#ffe0b2;padding:2px 6px">Using filesort</span> = expensive sort operation<br>';
                    html+='<span style="background:#ffe0b2;padding:2px 6px">Using temporary</span> = creates temp table</p>';
                }
            }
            $('#shopos-digital-modal-body').html(html);
            $('#shopos-digital-prof-modal').show();
        }
    });
});
$('.shopos-digital-modal-close, .shopos-digital-modal-overlay').on('click',function(){$('#shopos-digital-prof-modal').hide()});

// Tools: copy/download/import
$('#shopos-digital-copy').on('click',function(){$('#shopos-digital-export').select();document.execCommand('copy');var b=$(this);b.text('✅ Copied!');setTimeout(function(){b.text('📋 Copy')},2000)});
$('#shopos-digital-download').on('click',function(){
    var d=$('#shopos-digital-export').val(),b=new Blob([d],{type:'application/json'}),u=URL.createObjectURL(b),a=document.createElement('a');
    a.href=u;a.download='shopos-digital-'+new Date().toISOString().slice(0,10)+'.json';a.click();URL.revokeObjectURL(u);
});
$('#shopos-digital-do-import').on('click',function(){
    var raw=$('#shopos-digital-import').val().trim();
    if(!raw){$('#shopos-digital-import-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Paste JSON first.</div>');return}
    try{JSON.parse(raw)}catch(e){$('#shopos-digital-import-msg').html('<div class="shopos-digital-alert shopos-digital-warn">Invalid JSON.</div>');return}
    fdConfirm({
        title: 'Import Settings',
        body: '<p>Overwrite <strong>all current settings</strong> with the pasted JSON? Every tab will be replaced.</p>',
        okLabel: '📥 Overwrite & Import',
        needsBackup: false
    }, function(){
        var b=$('#shopos-digital-do-import');b.prop('disabled',true);
        $.post(A,{action:'shopos_digital_import_settings',nonce:N,settings:raw},function(r){
            b.prop('disabled',false);
            if(r.success){$('#shopos-digital-import-msg').html('<div class="shopos-digital-alert shopos-digital-ok">'+r.data.message+' Reloading...</div>');setTimeout(function(){location.reload()},1500)}
            else $('#shopos-digital-import-msg').html('<div class="shopos-digital-alert shopos-digital-warn">'+(r.data.message||'Failed.')+'</div>');
        });
    });
});

});
})(jQuery);
