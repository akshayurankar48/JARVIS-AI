import { Container, Title, Input, Select, Text } from '@bsf/force-ui';
import { Palette, Type, MessageSquare } from 'lucide-react';

const TONE_OPTIONS = [
	{ value: '', label: 'Not set' },
	{ value: 'professional', label: 'Professional' },
	{ value: 'friendly', label: 'Friendly' },
	{ value: 'casual', label: 'Casual' },
	{ value: 'authoritative', label: 'Authoritative' },
	{ value: 'playful', label: 'Playful' },
	{ value: 'minimal', label: 'Minimal' },
];

const FONT_OPTIONS = [
	{ value: '', label: 'Not set (use theme default)' },
	{ value: 'sans-serif', label: 'Sans-serif (modern, clean)' },
	{ value: 'serif', label: 'Serif (traditional, elegant)' },
	{ value: 'monospace', label: 'Monospace (technical, code-like)' },
];

function ColorField( { label, value, onChange, placeholder = '#000000' } ) {
	return (
		<div className="flex flex-col gap-1.5 flex-1">
			<Text size="sm" className="text-text-secondary font-medium">
				{ label }
			</Text>
			<div className="flex items-center gap-2">
				<input
					type="color"
					value={ value || placeholder }
					onChange={ ( e ) => onChange( e.target.value ) }
					className="w-9 h-9 rounded-md border border-solid border-border-subtle cursor-pointer p-0.5 bg-background-primary"
				/>
				<Input
					size="sm"
					placeholder={ placeholder }
					value={ value }
					onChange={ ( v ) => onChange( v ) }
					className="flex-1"
				/>
			</div>
		</div>
	);
}

export default function BrandPresets( { brand = {}, onBrandChange } ) {
	const update = ( key, value ) => {
		onBrandChange?.( { ...brand, [ key ]: value } );
	};

	return (
		<Container direction="column" gap="lg">
			{ /* Brand Identity */ }
			<Container direction="column" gap="md">
				<Container direction="row" align="center" gap="sm">
					<Type size={ 20 } className="text-icon-secondary" />
					<Title
						title="Brand Identity"
						description="Your brand name and tagline are used by the AI when generating content and pages."
						size="sm"
					/>
				</Container>

				<div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
					<div className="flex flex-col gap-1.5">
						<Text
							size="sm"
							className="text-text-secondary font-medium"
						>
							Brand Name
						</Text>
						<Input
							size="md"
							placeholder="e.g. Acme Inc"
							value={ brand.brand_name || '' }
							onChange={ ( v ) => update( 'brand_name', v ) }
						/>
					</div>
					<div className="flex flex-col gap-1.5">
						<Text
							size="sm"
							className="text-text-secondary font-medium"
						>
							Tagline
						</Text>
						<Input
							size="md"
							placeholder="e.g. Build better, faster"
							value={ brand.tagline || '' }
							onChange={ ( v ) => update( 'tagline', v ) }
						/>
					</div>
				</div>
			</Container>

			{ /* Brand Colors */ }
			<Container direction="column" gap="md">
				<Container direction="row" align="center" gap="sm">
					<Palette size={ 20 } className="text-icon-secondary" />
					<Title
						title="Brand Colors"
						description="The AI will use these colors when building pages and generating designs instead of random palettes."
						size="sm"
					/>
				</Container>

				<div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
					<ColorField
						label="Primary"
						value={ brand.primary_color || '' }
						onChange={ ( v ) => update( 'primary_color', v ) }
						placeholder="#6366f1"
					/>
					<ColorField
						label="Accent"
						value={ brand.accent_color || '' }
						onChange={ ( v ) => update( 'accent_color', v ) }
						placeholder="#06b6d4"
					/>
					<ColorField
						label="Dark"
						value={ brand.dark_color || '' }
						onChange={ ( v ) => update( 'dark_color', v ) }
						placeholder="#0f172a"
					/>
					<ColorField
						label="Light"
						value={ brand.light_color || '' }
						onChange={ ( v ) => update( 'light_color', v ) }
						placeholder="#f8fafc"
					/>
				</div>

				{ /* Color preview */ }
				{ ( brand.primary_color ||
					brand.accent_color ||
					brand.dark_color ||
					brand.light_color ) && (
					<div className="flex gap-2 items-center">
						<Text
							size="sm"
							className="text-text-tertiary mr-1"
						>
							Preview:
						</Text>
						{ [
							brand.primary_color,
							brand.accent_color,
							brand.dark_color,
							brand.light_color,
						]
							.filter( Boolean )
							.map( ( color, i ) => (
								<div
									key={ i }
									className="w-8 h-8 rounded-md border border-solid border-border-subtle"
									style={ { backgroundColor: color } }
								/>
							) ) }
					</div>
				) }
			</Container>

			{ /* Writing Tone & Font */ }
			<Container direction="column" gap="md">
				<Container direction="row" align="center" gap="sm">
					<MessageSquare
						size={ 20 }
						className="text-icon-secondary"
					/>
					<Title
						title="Writing Style"
						description="Set the default tone and typography preference for AI-generated content."
						size="sm"
					/>
				</Container>

				<div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
					<div className="flex flex-col gap-1.5">
						<Text
							size="sm"
							className="text-text-secondary font-medium"
						>
							Writing Tone
						</Text>
						<Select
							size="md"
							value={ brand.tone || '' }
							onChange={ ( v ) => update( 'tone', v ) }
						>
							<Select.Button placeholder="Select tone" />
							<Select.Options>
								{ TONE_OPTIONS.map( ( opt ) => (
									<Select.Option
										key={ opt.value }
										value={ opt.value }
									>
										{ opt.label }
									</Select.Option>
								) ) }
							</Select.Options>
						</Select>
					</div>
					<div className="flex flex-col gap-1.5">
						<Text
							size="sm"
							className="text-text-secondary font-medium"
						>
							Font Preference
						</Text>
						<Select
							size="md"
							value={ brand.font_preference || '' }
							onChange={ ( v ) =>
								update( 'font_preference', v )
							}
						>
							<Select.Button placeholder="Select font style" />
							<Select.Options>
								{ FONT_OPTIONS.map( ( opt ) => (
									<Select.Option
										key={ opt.value }
										value={ opt.value }
									>
										{ opt.label }
									</Select.Option>
								) ) }
							</Select.Options>
						</Select>
					</div>
				</div>
			</Container>

			<Text size="sm" className="text-text-tertiary">
				All fields are optional. The AI will fall back to the active
				theme defaults when brand settings are not configured.
			</Text>
		</Container>
	);
}
