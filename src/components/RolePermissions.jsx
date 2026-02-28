import { useState } from '@wordpress/element';
import { Container, Title, Switch, Text } from '@bsf/force-ui';
import { Shield } from 'lucide-react';

const DEFAULT_ROLES = [
	{ slug: 'administrator', label: 'Administrator', enabled: true, locked: true },
	{ slug: 'editor', label: 'Editor', enabled: true, locked: false },
	{ slug: 'author', label: 'Author', enabled: false, locked: false },
	{ slug: 'contributor', label: 'Contributor', enabled: false, locked: false },
	{ slug: 'subscriber', label: 'Subscriber', enabled: false, locked: false },
];

export default function RolePermissions() {
	const [ roles, setRoles ] = useState( DEFAULT_ROLES );

	const toggleRole = ( slug ) => {
		setRoles( ( prev ) =>
			prev.map( ( role ) =>
				role.slug === slug
					? { ...role, enabled: ! role.enabled }
					: role
			)
		);
	};

	return (
		<div className="rounded-lg border border-border-subtle bg-background-primary p-6">
			<Container direction="column" gap="md">
				<Container direction="row" align="center" gap="sm">
					<Shield size={ 20 } className="text-icon-secondary" />
					<Title
						title="Role Permissions"
						description="Choose which WordPress roles can interact with the AI agent."
						size="sm"
					/>
				</Container>

				<Container direction="column" gap="xs">
					{ roles.map( ( role ) => (
						<Container
							key={ role.slug }
							direction="row"
							justify="between"
							align="center"
							className="rounded-md border border-border-subtle px-4 py-3"
						>
							<Container direction="column" gap="xs">
								<Text size="sm" weight="medium">
									{ role.label }
								</Text>
								{ role.locked && (
									<Text size="xs" color="secondary">
										Always enabled
									</Text>
								) }
							</Container>
							<Switch
								size="sm"
								value={ role.enabled }
								onChange={ () => toggleRole( role.slug ) }
								disabled={ role.locked }
							/>
						</Container>
					) ) }
				</Container>
			</Container>
		</div>
	);
}
