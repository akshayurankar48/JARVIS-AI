import { Container, Title, Badge, Button, Toaster, toast } from '@bsf/force-ui';
import { Save } from 'lucide-react';
import ApiKeyForm from '../components/ApiKeyForm';
import ModelSelector from '../components/ModelSelector';
import RolePermissions from '../components/RolePermissions';
import StatusCard from '../components/StatusCard';
import QuickStats from '../components/QuickStats';

const { version } = window.wpAgentData || {};

export default function Settings() {
	const handleSave = () => {
		toast.success( 'Settings saved successfully!', {
			description: 'Your WP Agent configuration has been updated.',
		} );
	};

	return (
		<>
			<Toaster position="top-right" />
			<div className="min-h-screen bg-background-primary p-6 md:p-8">
				{ /* Header */ }
				<Container
					direction="row"
					justify="between"
					align="center"
					className="mb-8"
				>
					<Container direction="row" align="center" gap="sm">
						<Title
							title="Settings"
							description="Configure your WP Agent preferences"
							size="md"
							tag="h1"
						/>
						{ version && (
							<Badge
								label={ `v${ version }` }
								variant="neutral"
								size="xs"
							/>
						) }
					</Container>
					<Button
						variant="primary"
						size="md"
						icon={ <Save size={ 16 } /> }
						onClick={ handleSave }
					>
						Save Settings
					</Button>
				</Container>

				{ /* Two-column layout */ }
				<div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
					{ /* Left column — settings */ }
					<div className="lg:col-span-2 flex flex-col gap-6">
						<ApiKeyForm />
						<ModelSelector />
						<RolePermissions />
					</div>

					{ /* Right column — status */ }
					<div className="flex flex-col gap-6">
						<StatusCard />
						<QuickStats />
					</div>
				</div>
			</div>
		</>
	);
}
