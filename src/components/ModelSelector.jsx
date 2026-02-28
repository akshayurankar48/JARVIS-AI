import { useState } from '@wordpress/element';
import { Container, Title, Select, Text } from '@bsf/force-ui';
import { Cpu } from 'lucide-react';

const MODELS = [
	{
		value: 'google/gemini-flash-1.5',
		label: 'Gemini Flash — Fast',
		description: 'Quick responses for simple tasks.',
	},
	{
		value: 'openai/gpt-4o-mini',
		label: 'GPT-4o Mini — Balanced',
		description: 'Great balance of speed and quality. Recommended.',
	},
	{
		value: 'anthropic/claude-sonnet',
		label: 'Claude Sonnet — Powerful',
		description: 'Best quality for complex tasks.',
	},
];

export default function ModelSelector() {
	const [ model, setModel ] = useState( 'openai/gpt-4o-mini' );

	const selectedModel = MODELS.find( ( m ) => m.value === model );

	return (
		<div className="rounded-lg border border-border-subtle bg-background-primary p-6">
			<Container direction="column" gap="md">
				<Container direction="row" align="center" gap="sm">
					<Cpu size={ 20 } className="text-icon-secondary" />
					<Title
						title="AI Model"
						description="Choose which model the agent uses. You can always change this later."
						size="sm"
					/>
				</Container>

				<Select
					size="md"
					value={ model }
					onChange={ ( value ) => setModel( value ) }
				>
					<Select.Button placeholder="Select a model" />
					<Select.Options>
						{ MODELS.map( ( m ) => (
							<Select.Option key={ m.value } value={ m.value }>
								{ m.label }
							</Select.Option>
						) ) }
					</Select.Options>
				</Select>

				{ selectedModel && (
					<Text size="sm" color="secondary">
						{ selectedModel.description }
					</Text>
				) }
			</Container>
		</div>
	);
}
