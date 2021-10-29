/**
 * This sorting plug-in for DataTables will correctly sort date in short format - `dd.mm.YYYY`.
 *
 *  @name Date (dd.mm.YYYY)
 *  @summary Sort date in the format `dd.mm.YYYY`.
 *  @author [Ronny Vedrilla](http://www.ambient-innovation.com) & [Kort] (http://402km.ru)
 *
 *  @example
 *    $('#example').dataTable( {
 *       columnDefs: [
 *         { type: 'short_date', targets: 0 }
 *       ]
 *    } );
 */

 jQuery.extend( jQuery.fn.dataTableExt.oSort, {
	"short_date-asc": function ( a, b ) {
		var x, y;
		if ($.trim(a) !== '') {
			var shortDatea = $.trim(a).split('.');
			x = (shortDatea[2] + shortDatea[1] + shortDatea[0]) * 1;
		} else {
			x = Infinity; // = l'an 1000 ...
		}

		if ($.trim(b) !== '') {
			var shortDateb = $.trim(b).split('.');
			y = (shortDateb[2] + shortDateb[1] + shortDateb[0]) * 1;
		} else {
			y = Infinity;
		}
		var z = ((x < y) ? -1 : ((x > y) ? 1 : 0));
		return z;
	},

	"short_date-desc": function ( a, b ) {
		var x, y;
		if ($.trim(a) !== '') {
			var shortDatea = $.trim(a).split('.');
			x = (shortDatea[2] + shortDatea[1] + shortDatea[0]) * 1;
		} else {
			x = Infinity;
		}

		if ($.trim(b) !== '') {
			var shortDateb = $.trim(b).split('.');
			y = (shortDateb[2] + shortDateb[1] + shortDateb[0]) * 1;
		} else {
			y = Infinity;
		}
		var z = ((x < y) ? 1 : ((x > y) ? -1 : 0));
		return z;
	}
} );

