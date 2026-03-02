/**
 * Voice Input Component.
 *
 * Microphone button that uses the Web Speech API (SpeechRecognition)
 * to convert speech to text. Streams transcript into a callback.
 *
 * @package
 * @since 1.1.0
 */

import { useState, useRef, useCallback, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { css } from '@emotion/css';

const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

const micButtonStyle = css`
	min-width: 36px !important;
	height: 36px !important;
	padding: 0 !important;
	border-radius: 50% !important;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.2s ease;
	border: none !important;
	background: rgba(79, 70, 229, 0.08) !important;
	color: #6366f1 !important;

	&:hover {
		background: rgba(79, 70, 229, 0.15) !important;
		transform: scale(1.08);
	}

	svg {
		width: 18px;
		height: 18px;
		fill: currentColor;
	}
`;

const recordingStyle = css`
	background: rgba(239, 68, 68, 0.15) !important;
	color: #ef4444 !important;
	animation: pulse-recording 1.2s ease-in-out infinite;
	box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;

	@keyframes pulse-recording {
		0%, 100% { opacity: 1; transform: scale(1); }
		50% { opacity: 0.7; transform: scale(1.05); }
	}
`;

const MicIcon = () => (
	<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5z" />
		<path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z" />
	</svg>
);

const MicOffIcon = () => (
	<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<path d="M19 11h-1.7c0 .74-.16 1.43-.43 2.05l1.23 1.23c.56-.98.9-2.09.9-3.28zm-4.02.17c0-.06.02-.11.02-.17V5c0-1.66-1.34-3-3-3S9 3.34 9 5v.18l5.98 5.99zM4.27 3L3 4.27l6.01 6.01V11c0 1.66 1.33 3 2.99 3 .22 0 .44-.03.65-.08l1.66 1.66c-.71.33-1.5.52-2.31.52-2.76 0-5.3-2.1-5.3-5.1H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c.91-.13 1.77-.45 2.54-.9L19.73 21 21 19.73 4.27 3z" />
	</svg>
);

export default function VoiceInput( { onTranscript, onFinalTranscript, onRecordingEnd, disabled } ) {
	const [ isRecording, setIsRecording ] = useState( false );
	const recognitionRef = useRef( null );
	const silenceTimerRef = useRef( null );

	// Feature detection.
	if ( ! SpeechRecognition ) {
		return null;
	}

	const stopRecording = useCallback( () => {
		if ( recognitionRef.current ) {
			recognitionRef.current.stop();
			recognitionRef.current = null;
		}
		if ( silenceTimerRef.current ) {
			clearTimeout( silenceTimerRef.current );
			silenceTimerRef.current = null;
		}
		setIsRecording( false );
		if ( onRecordingEnd ) {
			onRecordingEnd();
		}
	}, [ onRecordingEnd ] );

	const startRecording = useCallback( () => {
		const recognition = new SpeechRecognition();
		recognition.continuous = true;
		recognition.interimResults = true;
		recognition.lang = document.documentElement.lang || 'en-US';

		recognition.onresult = ( event ) => {
			let interimTranscript = '';
			let finalTranscript = '';

			for ( let i = event.resultIndex; i < event.results.length; i++ ) {
				const transcript = event.results[ i ][ 0 ].transcript;
				if ( event.results[ i ].isFinal ) {
					finalTranscript += transcript;
				} else {
					interimTranscript += transcript;
				}
			}

			if ( finalTranscript && onFinalTranscript ) {
				onFinalTranscript( finalTranscript );
			}

			if ( interimTranscript && onTranscript ) {
				onTranscript( interimTranscript );
			}

			// Reset silence timer on new speech.
			if ( silenceTimerRef.current ) {
				clearTimeout( silenceTimerRef.current );
			}
			silenceTimerRef.current = setTimeout( () => {
				stopRecording();
			}, 2000 );
		};

		recognition.onerror = ( event ) => {
			if ( event.error !== 'aborted' ) {
				console.error( 'Speech recognition error:', event.error );
			}
			stopRecording();
		};

		recognition.onend = () => {
			setIsRecording( false );
		};

		recognitionRef.current = recognition;
		recognition.start();
		setIsRecording( true );
	}, [ onTranscript, onFinalTranscript, stopRecording ] );

	const toggleRecording = useCallback( () => {
		if ( isRecording ) {
			stopRecording();
		} else {
			startRecording();
		}
	}, [ isRecording, startRecording, stopRecording ] );

	// Cleanup on unmount.
	useEffect( () => {
		return () => {
			if ( recognitionRef.current ) {
				recognitionRef.current.stop();
			}
			if ( silenceTimerRef.current ) {
				clearTimeout( silenceTimerRef.current );
			}
		};
	}, [] );

	return (
		<Button
			className={ `${ micButtonStyle } ${ isRecording ? recordingStyle : '' }` }
			onClick={ toggleRecording }
			disabled={ disabled }
			label={ isRecording ? 'Stop recording' : 'Voice input' }
			showTooltip
		>
			{ isRecording ? <MicOffIcon /> : <MicIcon /> }
		</Button>
	);
}
