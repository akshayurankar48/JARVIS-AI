import { Container, Title, Badge, Text } from '@bsf/force-ui';
import { Activity } from 'lucide-react';

const STATUS_ITEMS = [
	{ label: 'API Connection', value: 'Not configured', variant: 'yellow' },
	{ label: 'Current Model', value: 'GPT-4o Mini', variant: 'neutral' },
	{ label: 'Rate Limit', value: '200 req/min', variant: 'green' },
];

export default function StatusCard() {
	return (
		<div className="rounded-lg border border-border-subtle bg-background-primary p-6">
			<Container direction="column" gap="md">
				<Container direction="row" align="center" gap="sm">
					<Activity size={ 20 } className="text-icon-secondary" />
					<Title title="Status" size="sm" />
				</Container>

				<Container direction="column" gap="sm">
					{ STATUS_ITEMS.map( ( item ) => (
						<Container
							key={ item.label }
							direction="row"
							justify="between"
							align="center"
						>
							<Text size="sm" color="secondary">
								{ item.label }
							</Text>
							<Badge
								label={ item.value }
								variant={ item.variant }
								size="xs"
							/>
						</Container>
					) ) }
				</Container>
			</Container>
		</div>
	);
}
