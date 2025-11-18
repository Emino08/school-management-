# Specification: Automatic Speech Recognition (ASR) Feature

## 1. Overview

This document outlines the backend requirements for integrating an Automatic Speech Recognition (ASR) service into the School Management System. The provided reference code is written in Python and utilizes advanced machine learning models (e.g., Transducer, WKV-based attention) for speech-to-text conversion.

Since the current backend is a PHP application, it cannot execute the Python code directly. Therefore, a microservice architecture is the recommended approach.

## 2. Proposed Architecture

The ASR functionality will be encapsulated in a new, independent Python service. The existing PHP backend will communicate with this service via an internal API.

1.  **ASR Microservice (Python)**:
    *   A new service built (e.g., using Flask or FastAPI) to wrap the Python ASR code.
    *   It will expose a single endpoint, such as `/transcribe`, that accepts audio data.
    *   This service will be responsible for all ML-related tasks: audio preprocessing (frontend feature extraction), running the model (encoder-decoder), and returning the transcribed text.
    *   The provided code snippets for `AutoFrontend`, model `__init__`, `WKVLinearAttention`, and VAD `init_cache` would form the core of this service.
    *   It should be containerized using Docker for portability and easy deployment.

2.  **Main Backend (`backend1` - PHP)**:
    *   A new API endpoint will be created, for example: `POST /api/speech/transcribe`.
    *   This endpoint will be responsible for receiving audio file uploads from the frontend.
    *   It will perform initial validation (e.g., user authentication, file type, size).
    *   It will then forward the request containing the audio data to the Python ASR microservice.
    *   Upon receiving the transcription from the ASR service, it will return the text to the frontend.

## 3. API Specification

### New Endpoint in `backend1`

*   **Route**: `POST /api/speech/transcribe`
*   **Description**: Receives an audio file, forwards it to the ASR service for transcription, and returns the result.
*   **Request**:
    *   **Content-Type**: `multipart/form-data`
    *   **Body**:
        *   `audio`: The audio file to be transcribed (e.g., `.wav`, `.mp3`, `.webm`).
*   **Response (Success)**:
    *   **Status**: `200 OK`
    *   **Body**:
        ```json
        {
          "transcription": "This is the transcribed text from the audio."
        }
        ```
*   **Response (Error)**:
    *   **Status**: `400 Bad Request`, `500 Internal Server Error`
    *   **Body**:
        ```json
        {
          "error": "A description of the error."
        }
        ```

## 4. Summary of Python Snippets' Roles

*   **`AutoFrontend` Class**: Manages the audio preprocessing pipeline. It loads audio, extracts features like FBanks, and prepares data in batches for the neural network. This would be a key component of the ASR microservice.
*   **Model `__init__` Methods**: Define the neural network architecture. The snippets show a complex Transducer model with components like an encoder, decoder, and joint network, along with SpecAugment for data augmentation. This is the "brain" of the ASR service.
*   **`init_cache` Method**: Implements caching for streaming inference, which is crucial for real-time transcription and Voice Activity Detection (VAD).
*   **`forward` Methods (`WKVLinearAttention`)**: These define the core forward pass of a custom attention layer in the model, optimized for performance.
*   **`frontend_for` Function**: A factory function to construct the speech enhancement and feature extraction part of the model.
