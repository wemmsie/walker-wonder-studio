import { Container } from '@newfold/ui-component-library';
import MyProductsTable from '../myProductsTable';

const defaults = {
	text: {
		jarvisText: __(
			'Please login to your account manager to see products.',
			'wp-module-my-products'
		),
		noProducts: __(
			'Sorry, no products. Please, try again later.',
			'wp-module-my-products'
		),
	},
};

/**
 * Products Module
 * For use in brand app to display user's products
 *
 * @param {*} props
 * @return {JSX.Element} Products Module
 */
const NewfoldMyProducts = ( { methods, constants, ...props } ) => {
	const [ userProducts, setUserProducts ] = methods.useState( [] );
	const [ isLoading, setIsLoading ] = methods.useState( true );
	const [ isError, setIsError ] = methods.useState( false );
	const [ errorMsg, setErrorMsg ] = methods.useState( '' );

	// set defaults if not provided
	constants = Object.assign( defaults, constants );

	const fetchUserProducts = async () => {
		try {
			if ( methods.isJarvis() ) {
				const response = await methods.apiFetch( {
					url: methods.NewfoldRuntime.createApiUrl(
						'/newfold-my-products/v1/products'
					),
					method: 'GET',
				} );
				if ( ! response ) {
					throw new Error( 'Failed to fetch data' );
				}
				if ( ! Array.isArray( response ) && response.length === 0 ) {
					throw new Error( 'Empty products list' );
				}
				setUserProducts( response );
				setIsLoading( false );
			}
		} catch ( error ) {
			setIsError( true );
			setIsLoading( false );
			if ( error.message === 'Empty products list' ) {
				setErrorMsg( constants.text.noProducts );
			}
			setUserProducts( [] );
		}
	};

	useEffect( () => {
		fetchUserProducts();
	}, [] );

	if ( isLoading || ( isError && ! errorMsg ) ) {
		return null;
	}

	return (
		<Container className="newfold-my-products">
			<Container.Header title={ constants.text.title }>
				<p>
					{ constants.text.subTitle }
					<a href={ constants.text.renewalCenterUrl }>
						{ constants.text.renewalText }
					</a>
				</p>
			</Container.Header>
			<Container.Block>
				{ isError ? errorMsg : (
					<MyProductsTable
						methods={ methods }
						constants={ constants }
						userProducts={ userProducts }
					/>
				) }
			</Container.Block>
		</Container>
	);
};

export default NewfoldMyProducts;
