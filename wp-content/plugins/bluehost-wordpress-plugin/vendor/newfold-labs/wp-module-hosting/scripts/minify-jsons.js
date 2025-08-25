const fs = require( 'fs' );
const path = require( 'path' );

const EXCLUDED_FILES = [ 'package.json', 'package-lock.json', 'composer.json' ];
const EXCLUDED_DIRS = [ 'node_modules', 'vendor' ];

const minifyJsonFiles = ( dir ) => {
	fs.readdirSync( dir ).forEach( ( file ) => {
		const fullPath = path.join( dir, file );
		const stat = fs.statSync( fullPath );

		if ( stat.isDirectory() ) {
			if ( EXCLUDED_DIRS.includes( file ) ) {
				console.log( `Skipping directory ${ file }` );
				return;
			}
			minifyJsonFiles( fullPath );
		} else if (
			file.endsWith( '.json' ) &&
			! EXCLUDED_FILES.includes( file )
		) {
			console.log( `Minifying ${ fullPath }` );
			const raw = fs.readFileSync( fullPath, 'utf-8' );
			try {
				const minified = JSON.stringify( JSON.parse( raw ) );
				fs.writeFileSync( fullPath, minified );
			} catch ( e ) {
				console.error( `Skipped invalid JSON: ${ fullPath }` );
			}
		} else if ( EXCLUDED_FILES.includes( file ) ) {
			console.log( `Skipping file ${ file }` );
		}
	} );
};

minifyJsonFiles( './' );
