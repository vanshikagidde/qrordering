<?php
/**
 * QR Order - Smart Ordering Platform
 * Landing Page for Shop Owners
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Order - Smart Ordering Platform for Your Business</title>
    <meta name="description" content="Transform your restaurant with QR code ordering. Customers scan, order, and enjoy. Easy setup for shop owners.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #F6921E;
            --primary-dark: #E07E0A;
            --secondary: #000000;
            --background: #FFFFFF;
            --background-alt: #F5F5F5;
            --text: #333333;
            --text-light: #666666;
            --border: #E0E0E0;
            --accent-glow: rgba(246, 146, 30, 0.4);
            
            --ease-elastic: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --ease-expo-out: cubic-bezier(0.16, 1, 0.3, 1);
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background);
            color: var(--text);
            overflow-x: hidden;
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            transition: all 0.4s var(--ease-smooth);
        }
        
        .header.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            height: 70px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: var(--secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.3s var(--ease-smooth);
        }
        
        .logo i {
            color: var(--primary);
            font-size: 32px;
        }
        
        .header.scrolled .logo {
            transform: scale(0.9);
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 40px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            font-size: 15px;
            position: relative;
            transition: color 0.3s var(--ease-smooth);
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: all 0.3s var(--ease-smooth);
            transform: translateX(-50%);
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s var(--ease-smooth);
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 20px rgba(246, 146, 30, 0.3);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 30px rgba(246, 146, 30, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--secondary);
            border: 2px solid var(--secondary);
        }
        
        .btn-outline:hover {
            background: var(--secondary);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-large {
            padding: 16px 40px;
            font-size: 16px;
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 55% 45%;
            align-items: center;
            padding: 100px 5% 50px;
            position: relative;
            overflow: hidden;
            perspective: 1200px;
        }
        
        .hero-bg {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 30%, rgba(246, 146, 30, 0.05) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(246, 146, 30, 0.03) 0%, transparent 50%);
            animation: gradientShift 20s ease-in-out infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 0%, 100% 100%; }
            50% { background-position: 100% 100%, 0% 0%; }
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            transform-style: preserve-3d;
        }
        
        .hero-headline {
            font-size: clamp(36px, 5vw, 64px);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
        }
        
        .hero-headline span {
            display: inline-block;
            opacity: 0;
            transform: rotateX(-90deg);
        }
        
        .hero-headline .word1 { color: var(--primary); }
        .hero-headline .word2 { color: var(--secondary); }
        .hero-headline .word3 { color: var(--primary); }
        
        .hero-subheadline {
            font-size: 18px;
            color: var(--text-light);
            line-height: 1.7;
            max-width: 500px;
            margin-bottom: 40px;
            opacity: 0;
            filter: blur(10px);
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .hero-btn-primary {
            opacity: 0;
            transform: scale(0.5);
        }
        
        .hero-btn-secondary {
            opacity: 0;
            transform: translateX(-30px);
        }
        
        .hero-visual {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
            transform-style: preserve-3d;
        }
        
        .phone-mockup {
            width: 100%;
            max-width: 350px;
            position: relative;
            opacity: 0;
            transform: rotateY(45deg) translateX(100px);
            animation: phoneFloat 6s ease-in-out infinite;
        }
        
        @keyframes phoneFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        .phone-mockup img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 30px 60px rgba(0, 0, 0, 0.2));
        }
        
        .qr-decoration {
            position: absolute;
            width: 200px;
            height: 200px;
            opacity: 0;
            animation: qrSpin 30s linear infinite, qrFloat 8s ease-in-out infinite;
        }
        
        .qr-decoration:nth-child(2) {
            top: 10%;
            right: 5%;
            width: 120px;
            height: 120px;
            animation-delay: 0s, 2s;
        }
        
        .qr-decoration:nth-child(3) {
            bottom: 20%;
            right: 15%;
            width: 80px;
            height: 80px;
            animation-delay: 0s, 4s;
        }
        
        @keyframes qrSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes qrFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .qr-decoration svg {
            width: 100%;
            height: 100%;
        }
        
        /* Particles */
        .particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.4;
            animation: particleFloat 8s ease-in-out infinite;
        }
        
        @keyframes particleFloat {
            0%, 100% { 
                transform: translateY(0) translateX(0);
                opacity: 0.4;
            }
            50% { 
                transform: translateY(-100px) translateX(50px);
                opacity: 0.6;
            }
        }
        
        /* Features Section */
        .features {
            padding: 100px 5%;
            background: var(--background-alt);
            position: relative;
        }
        
        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 60px;
        }
        
        .section-title {
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 16px;
        }
        
        .section-title .word {
            display: inline-block;
            opacity: 0;
            transform: translateY(40px);
        }
        
        .section-subtitle {
            font-size: 16px;
            color: var(--text-light);
            line-height: 1.6;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            perspective: 1000px;
        }
        
        .feature-card {
            background: white;
            border-radius: 24px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            transition: all 0.4s var(--ease-smooth);
            transform-style: preserve-3d;
            opacity: 0;
            transform: translateY(80px) rotateX(15deg);
        }
        
        .feature-card:nth-child(2) {
            transform: translateY(80px) rotateX(15deg);
        }
        
        .feature-card:hover {
            transform: translateY(-10px) translateZ(30px);
            box-shadow: 0 20px 60px rgba(246, 146, 30, 0.15);
            border-color: var(--primary);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
            color: white;
            transform: translateZ(20px);
            animation: iconPulse 4s ease-in-out infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .feature-card:hover .feature-icon {
            animation: iconSpin 0.6s var(--ease-elastic);
        }
        
        @keyframes iconSpin {
            from { transform: rotate(0deg) scale(1); }
            to { transform: rotate(360deg) scale(1.1); }
        }
        
        .feature-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 12px;
        }
        
        .feature-description {
            font-size: 15px;
            color: var(--text-light);
            line-height: 1.6;
        }
        
        /* How It Works Section */
        .how-it-works {
            padding: 100px 5%;
            background: white;
            position: relative;
        }
        
        .steps-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }
        
        .step {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            margin-bottom: 80px;
            opacity: 0;
        }
        
        .step:nth-child(even) .step-image {
            order: 2;
        }
        
        .step:nth-child(even) .step-content {
            order: 1;
        }
        
        .step-image {
            position: relative;
        }
        
        .step-image img {
            width: 100%;
            max-width: 400px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            transition: all 0.4s var(--ease-smooth);
        }
        
        .step-image:hover img {
            transform: scale(1.03);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
        }
        
        .step-number {
            font-size: 120px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
            opacity: 0;
            transform: rotateY(90deg) scale(0.5);
            animation: numberGlow 3s ease-in-out infinite;
        }
        
        @keyframes numberGlow {
            0%, 100% { text-shadow: 0 0 20px rgba(246, 146, 30, 0.3); }
            50% { text-shadow: 0 0 40px rgba(246, 146, 30, 0.6); }
        }
        
        .step-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 16px;
        }
        
        .step-description {
            font-size: 16px;
            color: var(--text-light);
            line-height: 1.7;
        }
        
        /* SVG Path */
        .steps-path {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        
        .steps-path svg {
            width: 100%;
            height: 100%;
        }
        
        .path-line {
            fill: none;
            stroke: var(--primary);
            stroke-width: 3;
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            stroke-linecap: round;
            opacity: 0.3;
        }
        
        /* Benefits Section */
        .benefits {
            padding: 100px 5%;
            background: var(--background-alt);
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .benefit-card {
            background: white;
            border-radius: 24px;
            padding: 40px 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            transition: all 0.35s var(--ease-smooth);
            opacity: 0;
            transform: rotate(var(--rotation)) scale(0.8);
        }
        
        .benefit-card:nth-child(1) { --rotation: -2deg; }
        .benefit-card:nth-child(2) { --rotation: 2deg; }
        .benefit-card:nth-child(3) { --rotation: 2deg; }
        .benefit-card:nth-child(4) { --rotation: -2deg; }
        
        .benefit-card:hover {
            transform: rotate(0deg) scale(1.05) translateY(-8px);
            box-shadow: 0 20px 60px rgba(246, 146, 30, 0.15);
        }
        
        .benefit-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 24px;
            animation: benefitBounce 3s ease-in-out infinite;
        }
        
        @keyframes benefitBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        
        .benefit-card:hover .benefit-icon {
            animation: benefitPop 0.3s var(--ease-elastic);
        }
        
        @keyframes benefitPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        
        .benefit-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 12px;
        }
        
        .benefit-description {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.6;
        }
        
        /* Testimonials Section */
        .testimonials {
            padding: 100px 5%;
            background: white;
        }
        
        .testimonials-carousel {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            perspective: 1200px;
        }
        
        .testimonials-track {
            display: flex;
            transition: transform 0.6s var(--ease-expo-out);
        }
        
        .testimonial-card {
            flex: 0 0 100%;
            padding: 40px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            text-align: center;
            transform-style: preserve-3d;
            transition: all 0.6s var(--ease-expo-out);
        }
        
        .testimonial-quote {
            font-size: 60px;
            color: var(--primary);
            opacity: 0.3;
            line-height: 1;
            margin-bottom: 20px;
            animation: quotePulse 4s ease-in-out infinite;
        }
        
        @keyframes quotePulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.5; }
        }
        
        .testimonial-text {
            font-size: 18px;
            color: var(--text);
            line-height: 1.8;
            margin-bottom: 30px;
            font-style: italic;
        }
        
        .testimonial-author {
            font-size: 18px;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .testimonial-role {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .testimonial-rating {
            color: var(--primary);
            font-size: 18px;
            margin-top: 15px;
        }
        
        .testimonial-rating i {
            animation: starTwinkle 2s ease-in-out infinite;
        }
        
        .testimonial-rating i:nth-child(2) { animation-delay: 0.2s; }
        .testimonial-rating i:nth-child(3) { animation-delay: 0.4s; }
        .testimonial-rating i:nth-child(4) { animation-delay: 0.6s; }
        .testimonial-rating i:nth-child(5) { animation-delay: 0.8s; }
        
        @keyframes starTwinkle {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .carousel-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 40px;
        }
        
        .carousel-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s var(--ease-smooth);
            font-size: 18px;
            color: var(--text);
        }
        
        .carousel-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: scale(1.15);
        }
        
        .carousel-dots {
            display: flex;
            gap: 10px;
        }
        
        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--border);
            cursor: pointer;
            transition: all 0.3s var(--ease-smooth);
        }
        
        .carousel-dot.active {
            background: var(--primary);
            transform: scale(1.2);
        }
        
        .carousel-dot:hover {
            transform: scale(1.3);
        }
        
        /* CTA Section */
        .cta {
            padding: 120px 5%;
            background: var(--secondary);
            position: relative;
            overflow: hidden;
        }
        
        .cta-bg {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse at 30% 20%, rgba(246, 146, 30, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 80%, rgba(246, 146, 30, 0.15) 0%, transparent 50%);
            animation: ctaGradientShift 15s ease-in-out infinite;
        }
        
        @keyframes ctaGradientShift {
            0%, 100% { background-position: 0% 0%, 100% 100%; }
            50% { background-position: 100% 100%, 0% 0%; }
        }
        
        .cta-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .cta-title {
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }
        
        .cta-title .word {
            display: inline-block;
            opacity: 0;
            transform: translateX(-50px);
        }
        
        .cta-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
            margin-bottom: 40px;
            opacity: 0;
            transform: translateY(30px);
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .cta-btn-primary {
            background: var(--primary);
            color: white;
            opacity: 0;
            transform: scale(0.5);
            box-shadow: 0 4px 20px rgba(246, 146, 30, 0.4);
            animation: ctaGlow 3s ease-in-out infinite;
        }
        
        @keyframes ctaGlow {
            0%, 100% { box-shadow: 0 4px 20px rgba(246, 146, 30, 0.4); }
            50% { box-shadow: 0 8px 40px rgba(246, 146, 30, 0.6); }
        }
        
        .cta-btn-primary:hover {
            background: var(--primary-dark);
            transform: scale(1.08);
        }
        
        .cta-btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
            opacity: 0;
        }
        
        .cta-btn-secondary:hover {
            background: white;
            color: var(--secondary);
        }
        
        .cta-floaters {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        
        .cta-qr {
            position: absolute;
            opacity: 0.1;
            animation: ctaQrFloat 5s ease-in-out infinite, ctaQrSpin 20s linear infinite;
        }
        
        .cta-qr:nth-child(1) {
            top: 10%;
            left: 10%;
            width: 100px;
            animation-delay: 0s, 0s;
        }
        
        .cta-qr:nth-child(2) {
            top: 60%;
            left: 5%;
            width: 60px;
            animation-delay: 2s, 0s;
        }
        
        .cta-qr:nth-child(3) {
            top: 20%;
            right: 10%;
            width: 80px;
            animation-delay: 4s, 0s;
        }
        
        @keyframes ctaQrFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-30px); }
        }
        
        @keyframes ctaQrSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Footer */
        .footer {
            background: var(--secondary);
            padding: 80px 5% 40px;
            color: white;
        }
        
        .footer-border {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin-bottom: 60px;
            transform: scaleX(0);
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 60px;
            max-width: 1200px;
            margin: 0 auto 60px;
        }
        
        .footer-brand .logo {
            color: white;
            margin-bottom: 20px;
        }
        
        .footer-brand p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            line-height: 1.7;
        }
        
        .footer-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 24px;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 12px;
            opacity: 0;
            transform: translateY(15px);
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s var(--ease-smooth);
            display: inline-block;
        }
        
        .footer-links a:hover {
            color: var(--primary);
            transform: translateX(5px);
        }
        
        .footer-contact p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(15px);
        }
        
        .footer-contact i {
            color: var(--primary);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-copyright {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            opacity: 0;
        }
        
        .footer-social {
            display: flex;
            gap: 15px;
        }
        
        .footer-social a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s var(--ease-smooth);
            opacity: 0;
            transform: scale(0);
        }
        
        .footer-social a:hover {
            background: var(--primary);
            transform: scale(1.2) rotate(10deg);
        }
        
        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            flex-direction: column;
            gap: 6px;
            cursor: pointer;
            z-index: 1001;
        }
        
        .mobile-menu-btn span {
            width: 28px;
            height: 3px;
            background: var(--secondary);
            transition: all 0.3s var(--ease-smooth);
            border-radius: 3px;
        }
        
        .mobile-menu-btn.active span:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }
        
        .mobile-menu-btn.active span:nth-child(2) {
            opacity: 0;
        }
        
        .mobile-menu-btn.active span:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }
        
        .mobile-menu {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            z-index: 999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 30px;
            opacity: 0;
            pointer-events: none;
            transition: all 0.4s var(--ease-smooth);
        }
        
        .mobile-menu.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .mobile-menu a {
            font-size: 24px;
            font-weight: 600;
            color: var(--secondary);
            text-decoration: none;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s var(--ease-smooth);
        }
        
        .mobile-menu.active a {
            opacity: 1;
            transform: translateY(0);
        }
        
        .mobile-menu a:nth-child(1) { transition-delay: 0.1s; }
        .mobile-menu a:nth-child(2) { transition-delay: 0.2s; }
        .mobile-menu a:nth-child(3) { transition-delay: 0.3s; }
        .mobile-menu a:nth-child(4) { transition-delay: 0.4s; }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 60px;
            }
            
            .hero-content {
                order: 2;
            }
            
            .hero-visual {
                order: 1;
            }
            
            .hero-subheadline {
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .phone-mockup {
                max-width: 280px;
            }
            
            .step {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .step:nth-child(even) .step-image,
            .step:nth-child(even) .step-content {
                order: unset;
            }
            
            .step-image img {
                margin: 0 auto;
            }
            
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .nav-buttons {
                display: none;
            }
            
            .mobile-menu-btn {
                display: flex;
            }
            
            .header {
                height: 70px;
            }
            
            .features-grid,
            .benefits-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
        
        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <a href="#" class="logo">
            <i class="fas fa-qrcode"></i>
            QR Order
        </a>
        
        <nav class="nav-links">
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#benefits">Benefits</a>
            <a href="#testimonials">Testimonials</a>
        </nav>
        
        <div class="nav-buttons">
            <a href="/shopowner/login.php" class="btn btn-outline">Login</a>
            <a href="/shopowner/register.php" class="btn btn-primary">Register</a>
        </div>
        
        <div class="mobile-menu-btn" id="mobileMenuBtn">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </header>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="#features">Features</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#benefits">Benefits</a>
        <a href="#testimonials">Testimonials</a>
        <a href="/shopowner/login.php" class="btn btn-outline">Login</a>
        <a href="/shopowner/register.php" class="btn btn-primary">Register</a>
    </div>

    <!-- Hero Section -->
    <section class="hero" id="hero">
        <div class="hero-bg"></div>
        
        <!-- Particles -->
        <div class="particles" id="particles"></div>
        
        <div class="hero-content">
            <h1 class="hero-headline">
                <span class="word1">Scan.</span>
                <span class="word2">Order.</span>
                <span class="word3">Enjoy.</span>
            </h1>
            <p class="hero-subheadline">
                The smartest way to order. QR-powered convenience for modern businesses. 
                Transform your restaurant with seamless digital ordering.
            </p>
            <div class="hero-buttons">
                <a href="/shopowner/register.php" class="btn btn-primary btn-large hero-btn-primary">
                    Get Started Free
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="#how-it-works" class="btn btn-outline btn-large hero-btn-secondary">
                    <i class="fas fa-play"></i>
                    Watch Demo
                </a>
            </div>
        </div>
        
        <div class="hero-visual">
            <div class="phone-mockup" id="phoneMockup">
                <img src="public/images/hero-phone.jpg" alt="QR Order App">
            </div>
            
            <!-- QR Decorations -->
            <div class="qr-decoration">
                <svg viewBox="0 0 100 100" fill="none">
                    <rect x="10" y="10" width="30" height="30" stroke="#F6921E" stroke-width="4" fill="none"/>
                    <rect x="15" y="15" width="20" height="20" fill="#F6921E"/>
                    <rect x="60" y="10" width="30" height="30" stroke="#F6921E" stroke-width="4" fill="none"/>
                    <rect x="65" y="15" width="20" height="20" fill="#F6921E"/>
                    <rect x="10" y="60" width="30" height="30" stroke="#F6921E" stroke-width="4" fill="none"/>
                    <rect x="15" y="65" width="20" height="20" fill="#F6921E"/>
                    <rect x="50" y="50" width="8" height="8" fill="#F6921E"/>
                    <rect x="65" y="50" width="8" height="8" fill="#F6921E"/>
                    <rect x="80" y="50" width="8" height="8" fill="#F6921E"/>
                    <rect x="50" y="65" width="8" height="8" fill="#F6921E"/>
                    <rect x="65" y="65" width="8" height="8" fill="#F6921E"/>
                    <rect x="80" y="65" width="8" height="8" fill="#F6921E"/>
                    <rect x="50" y="80" width="8" height="8" fill="#F6921E"/>
                    <rect x="65" y="80" width="8" height="8" fill="#F6921E"/>
                    <rect x="80" y="80" width="8" height="8" fill="#F6921E"/>
                </svg>
            </div>
            
            <div class="qr-decoration">
                <svg viewBox="0 0 100 100" fill="none">
                    <rect x="10" y="10" width="30" height="30" stroke="#F6921E" stroke-width="4" fill="none"/>
                    <rect x="15" y="15" width="20" height="20" fill="#F6921E"/>
                    <rect x="60" y="10" width="30" height="30" stroke="#F6921E" stroke-width="4" fill="none"/>
                    <rect x="65" y="15" width="20" height="20" fill="#F6921E"/>
                    <rect x="10" y="60" width="30" height="30" stroke="#F6921E" stroke-width="4" fill="none"/>
                    <rect x="15" y="65" width="20" height="20" fill="#F6921E"/>
                    <rect x="50" y="50" width="8" height="8" fill="#F6921E"/>
                    <rect x="65" y="50" width="8" height="8" fill="#F6921E"/>
                    <rect x="80" y="50" width="8" height="8" fill="#F6921E"/>
                    <rect x="50" y="65" width="8" height="8" fill="#F6921E"/>
                    <rect x="65" y="65" width="8" height="8" fill="#F6921E"/>
                    <rect x="80" y="65" width="8" height="8" fill="#F6921E"/>
                    <rect x="50" y="80" width="8" height="8" fill="#F6921E"/>
                    <rect x="65" y="80" width="8" height="8" fill="#F6921E"/>
                    <rect x="80" y="80" width="8" height="8" fill="#F6921E"/>
                </svg>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2 class="section-title">
                <span class="word">Powerful</span>
                <span class="word">Features</span>
            </h2>
            <p class="section-subtitle">
                Everything you need to streamline your ordering process and grow your business
            </p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h3 class="feature-title">Instant QR Codes</h3>
                <p class="feature-description">
                    Generate unique QR codes for each table in seconds. No technical skills required. Just print and place.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <h3 class="feature-title">Live Order Updates</h3>
                <p class="feature-description">
                    Receive orders instantly on your dashboard. Update menu availability in real-time with a single click.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="feature-title">Smart Analytics</h3>
                <p class="feature-description">
                    Track sales, popular items, and customer behavior with detailed insights to optimize your menu.
                </p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="section-header">
            <h2 class="section-title">
                <span class="word">How</span>
                <span class="word">It</span>
                <span class="word">Works</span>
            </h2>
            <p class="section-subtitle">
                Get started in three simple steps
            </p>
        </div>
        
        <div class="steps-container">
            <div class="step">
                <div class="step-image">
                    <img src="public/images/step-1-scan.jpg" alt="Scan QR Code">
                </div>
                <div class="step-content">
                    <div class="step-number">01</div>
                    <h3 class="step-title">Place the QR Code</h3>
                    <p class="step-description">
                        Customers scan the QR code at their table using any smartphone camera. No app download needed - it works with any device.
                    </p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-image">
                    <img src="public/images/step-2-order.jpg" alt="Place Order">
                </div>
                <div class="step-content">
                    <div class="step-number">02</div>
                    <h3 class="step-title">Customer Places Order</h3>
                    <p class="step-description">
                        They browse your digital menu with beautiful images and descriptions, then place orders directly from their phone.
                    </p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-image">
                    <img src="public/images/step-3-serve.jpg" alt="Receive and Serve">
                </div>
                <div class="step-content">
                    <div class="step-number">03</div>
                    <h3 class="step-title">Receive & Serve</h3>
                    <p class="step-description">
                        Orders appear instantly on your dashboard with table numbers. Prepare and serve with ease - no confusion, no delays.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits" id="benefits">
        <div class="section-header">
            <h2 class="section-title">
                <span class="word">Why</span>
                <span class="word">Choose</span>
                <span class="word">Us</span>
            </h2>
            <p class="section-subtitle">
                Benefits that transform your business
            </p>
        </div>
        
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3 class="benefit-title">Faster Service</h3>
                <p class="benefit-description">
                    Reduce order taking time by 70%. Serve more customers with the same staff and increase table turnover.
                </p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="benefit-title">Zero Miscommunication</h3>
                <p class="benefit-description">
                    Customers order directly. No more misheard items or written mistakes. Accuracy guaranteed.
                </p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h3 class="benefit-title">Lower Overhead</h3>
                <p class="benefit-description">
                    Reduce menu printing costs. Update prices instantly without reprinting. Save money and trees.
                </p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fas fa-smile"></i>
                </div>
                <h3 class="benefit-title">Better Experience</h3>
                <p class="benefit-description">
                    Modern, contactless ordering that customers love and recommend. Boost your brand image.
                </p>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="section-header">
            <h2 class="section-title">
                <span class="word">What</span>
                <span class="word">Our</span>
                <span class="word">Clients</span>
                <span class="word">Say</span>
            </h2>
            <p class="section-subtitle">
                Trusted by restaurants worldwide
            </p>
        </div>
        
        <div class="testimonials-carousel">
            <div class="testimonials-track" id="testimonialsTrack">
                <div class="testimonial-card">
                    <div class="testimonial-quote">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">
                        "QR Order transformed our service. Orders are faster, errors are gone, and customers love the modern experience. Our revenue increased by 25% in the first month!"
                    </p>
                    <div class="testimonial-author">Sarah Johnson</div>
                    <div class="testimonial-role">Owner, Bistro 42</div>
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-quote">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">
                        "The analytics dashboard alone is worth it. We finally understand what our customers want and can optimize our menu accordingly. Game changer!"
                    </p>
                    <div class="testimonial-author">Michael Chen</div>
                    <div class="testimonial-role">Manager, Spice Garden</div>
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-quote">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">
                        "Setup took 10 minutes. Our staff adapted immediately. Best investment we've made for our cafe. Customers keep asking about it!"
                    </p>
                    <div class="testimonial-author">Emma Rodriguez</div>
                    <div class="testimonial-role">Director, Cafe Delight</div>
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
            
            <div class="carousel-nav">
                <button class="carousel-btn" id="prevBtn">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="carousel-dots">
                    <div class="carousel-dot active" data-index="0"></div>
                    <div class="carousel-dot" data-index="1"></div>
                    <div class="carousel-dot" data-index="2"></div>
                </div>
                <button class="carousel-btn" id="nextBtn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta" id="cta">
        <div class="cta-bg"></div>
        
        <div class="cta-floaters">
            <div class="cta-qr">
                <svg viewBox="0 0 100 100" fill="#F6921E">
                    <rect x="10" y="10" width="30" height="30" stroke="currentColor" stroke-width="4" fill="none"/>
                    <rect x="15" y="15" width="20" height="20" fill="currentColor"/>
                    <rect x="60" y="10" width="30" height="30" stroke="currentColor" stroke-width="4" fill="none"/>
                    <rect x="65" y="15" width="20" height="20" fill="currentColor"/>
                    <rect x="10" y="60" width="30" height="30" stroke="currentColor" stroke-width="4" fill="none"/>
                    <rect x="15" y="65" width="20" height="20" fill="currentColor"/>
                </svg>
            </div>
            <div class="cta-qr">
                <svg viewBox="0 0 100 100" fill="#F6921E">
                    <rect x="10" y="10" width="30" height="30" stroke="currentColor" stroke-width="4" fill="none"/>
                    <rect x="15" y="15" width="20" height="20" fill="currentColor"/>
                    <rect x="60" y="10" width="30" height="30" stroke="currentColor" stroke-width="4" fill="none"/>
                    <rect x="65" y="15" width="20" height="20" fill="currentColor"/>
                    <rect x="10" y="60" width="30" height="30" stroke="currentColor" stroke-width="4" fill="none"/>
                    <rect x="15" y="65" width="20" height="20" fill="currentColor"/>
                </svg>
            </div>
            <div class="cta-qr">
                <svg viewBox="0 0 100 100" fill="#F6921E">
                    <rect x="10" y="10" width="30" height="30" stroke="currentColor" stroke-width="4" fill="none"/>
                    <rect x="15" y="15" width="20" height="20" fill="currentColor"/>
                    <rect x="60" y="10" width="30" height="30" stroke="currentColor" stroke-width="4" fill="none"/>
                    <rect x="65" y="15" width="20" height="20" fill="currentColor"/>
                    <rect x="10" y="60" width="30" height="30" stroke="currentColor" stroke-width="4" fill="none"/>
                    <rect x="15" y="65" width="20" height="20" fill="currentColor"/>
                </svg>
            </div>
        </div>
        
        <div class="cta-content">
            <h2 class="cta-title">
                <span class="word">Ready</span>
                <span class="word">to</span>
                <span class="word">Transform</span>
                <span class="word">Your</span>
                <span class="word">Ordering?</span>
            </h2>
            <p class="cta-subtitle">
                Join thousands of businesses already using QR Order. Start your free trial today and see the difference.
            </p>
            <div class="cta-buttons">
                <a href="/shopowner/register.php" class="btn btn-primary btn-large cta-btn-primary">
                    Get Started Free
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="/shopowner/login.php" class="btn btn-outline btn-large cta-btn-secondary">
                    Login to Dashboard
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-border" id="footerBorder"></div>
        
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="#" class="logo">
                    <i class="fas fa-qrcode"></i>
                    QR Order
                </a>
                <p>
                    Modern ordering for modern businesses. Transform your restaurant with seamless QR code technology.
                </p>
            </div>
            
            <div class="footer-column">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="#hero">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#benefits">Benefits</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4 class="footer-title">Support</h4>
                <ul class="footer-links">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">API Reference</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4 class="footer-title">Contact</h4>
                <div class="footer-contact">
                    <p><i class="fas fa-envelope"></i> hello@qrmenu.com</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Tech Street, San Francisco, CA</p>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p class="footer-copyright">
                © <?php echo date('Y'); ?> QR Order. All rights reserved.
            </p>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </footer>

    <script>
        // Register GSAP ScrollTrigger
        gsap.registerPlugin(ScrollTrigger);
        
        // Header scroll effect
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }, { passive: true });
        
        // Mobile menu
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenuBtn.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });
        
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenuBtn.classList.remove('active');
                mobileMenu.classList.remove('active');
            });
        });
        
        // Create particles
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 8 + 's';
            particle.style.animationDuration = (6 + Math.random() * 4) + 's';
            particlesContainer.appendChild(particle);
        }
        
        // Hero animations
        const heroTl = gsap.timeline({ delay: 0.3 });
        
        heroTl
            .to('.hero-headline .word1', {
                opacity: 1,
                rotateX: 0,
                duration: 0.6,
                ease: 'expo.out'
            })
            .to('.hero-headline .word2', {
                opacity: 1,
                rotateX: 0,
                duration: 0.6,
                ease: 'expo.out'
            }, '-=0.45')
            .to('.hero-headline .word3', {
                opacity: 1,
                rotateX: 0,
                duration: 0.6,
                ease: 'expo.out'
            }, '-=0.45')
            .to('.hero-subheadline', {
                opacity: 1,
                filter: 'blur(0px)',
                y: 0,
                duration: 0.7,
                ease: 'power2.out'
            }, '-=0.3')
            .to('.hero-btn-primary', {
                opacity: 1,
                scale: 1,
                duration: 0.6,
                ease: 'elastic.out(1, 0.5)'
            }, '-=0.4')
            .to('.hero-btn-secondary', {
                opacity: 1,
                x: 0,
                duration: 0.5,
                ease: 'expo.out'
            }, '-=0.4')
            .to('#phoneMockup', {
                opacity: 1,
                rotateY: 0,
                x: 0,
                duration: 1,
                ease: 'expo.out'
            }, '-=0.8')
            .to('.qr-decoration', {
                opacity: 0.15,
                scale: 1,
                rotate: 0,
                duration: 1.2,
                ease: 'elastic.out(1, 0.5)',
                stagger: 0.1
            }, '-=0.6');
        
        // Hero scroll effects
        gsap.to('.hero-headline', {
            y: -80,
            scrollTrigger: {
                trigger: '.hero',
                start: 'top top',
                end: '50% top',
                scrub: 1
            }
        });
        
        gsap.to('.hero-subheadline', {
            y: -40,
            scrollTrigger: {
                trigger: '.hero',
                start: 'top top',
                end: '50% top',
                scrub: 1
            }
        });
        
        gsap.to('#phoneMockup', {
            rotateY: -15,
            rotateX: 5,
            scale: 0.9,
            scrollTrigger: {
                trigger: '.hero',
                start: 'top top',
                end: '50% top',
                scrub: 1
            }
        });
        
        gsap.to('.hero-content', {
            opacity: 0,
            scrollTrigger: {
                trigger: '.hero',
                start: '30% top',
                end: '50% top',
                scrub: 1
            }
        });
        
        // Features section animations
        gsap.to('.features .section-title .word', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            ease: 'expo.out',
            stagger: 0.1,
            scrollTrigger: {
                trigger: '.features',
                start: 'top 80%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.features .section-subtitle', {
            opacity: 1,
            y: 0,
            duration: 0.5,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.features',
                start: 'top 75%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.feature-card', {
            opacity: 1,
            y: function(i) {
                return i === 1 ? 40 : 0;
            },
            rotateX: 0,
            duration: 0.7,
            ease: 'expo.out',
            stagger: 0.15,
            scrollTrigger: {
                trigger: '.features-grid',
                start: 'top 80%',
                toggleActions: 'play none none none'
            }
        });
        
        // How It Works section animations
        gsap.to('.how-it-works .section-title .word', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            ease: 'expo.out',
            stagger: 0.08,
            scrollTrigger: {
                trigger: '.how-it-works',
                start: 'top 80%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.how-it-works .section-subtitle', {
            opacity: 1,
            y: 0,
            duration: 0.5,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.how-it-works',
                start: 'top 75%',
                toggleActions: 'play none none none'
            }
        });
        
        document.querySelectorAll('.step').forEach((step, index) => {
            const isEven = index % 2 === 1;
            
            gsap.to(step, {
                opacity: 1,
                duration: 0.1,
                scrollTrigger: {
                    trigger: step,
                    start: 'top 80%',
                    toggleActions: 'play none none none',
                    onEnter: () => {
                        gsap.to(step.querySelector('.step-number'), {
                            opacity: 1,
                            rotateY: 0,
                            scale: 1,
                            duration: 0.6,
                            ease: 'elastic.out(1, 0.5)'
                        });
                        
                        gsap.fromTo(step.querySelector('.step-image img'), 
                            { x: isEven ? 60 : -60, opacity: 0 },
                            { x: 0, opacity: 1, duration: 0.7, ease: 'expo.out', delay: 0.1 }
                        );
                        
                        gsap.fromTo(step.querySelector('.step-content'),
                            { x: isEven ? -30 : 30, opacity: 0 },
                            { x: 0, opacity: 1, duration: 0.6, ease: 'expo.out', delay: 0.2 }
                        );
                    }
                }
            });
        });
        
        // Benefits section animations
        gsap.to('.benefits .section-title .word', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            ease: 'expo.out',
            stagger: 0.08,
            scrollTrigger: {
                trigger: '.benefits',
                start: 'top 80%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.benefits .section-subtitle', {
            opacity: 1,
            y: 0,
            duration: 0.5,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.benefits',
                start: 'top 75%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.benefit-card', {
            opacity: 1,
            scale: 1,
            duration: 0.7,
            ease: 'expo.out',
            stagger: 0.15,
            scrollTrigger: {
                trigger: '.benefits-grid',
                start: 'top 80%',
                toggleActions: 'play none none none'
            }
        });
        
        // Testimonials section animations
        gsap.to('.testimonials .section-title .word', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            ease: 'expo.out',
            stagger: 0.06,
            scrollTrigger: {
                trigger: '.testimonials',
                start: 'top 80%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.testimonials .section-subtitle', {
            opacity: 1,
            y: 0,
            duration: 0.5,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.testimonials',
                start: 'top 75%',
                toggleActions: 'play none none none'
            }
        });
        
        // Testimonials Carousel
        const track = document.getElementById('testimonialsTrack');
        const dots = document.querySelectorAll('.carousel-dot');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        let currentSlide = 0;
        const totalSlides = 3;
        
        function goToSlide(index) {
            currentSlide = index;
            track.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === currentSlide);
            });
        }
        
        prevBtn.addEventListener('click', () => {
            goToSlide((currentSlide - 1 + totalSlides) % totalSlides);
        });
        
        nextBtn.addEventListener('click', () => {
            goToSlide((currentSlide + 1) % totalSlides);
        });
        
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => goToSlide(index));
        });
        
        // Auto-advance carousel
        setInterval(() => {
            goToSlide((currentSlide + 1) % totalSlides);
        }, 5000);
        
        // CTA section animations
        gsap.to('.cta-title .word', {
            opacity: 1,
            x: 0,
            duration: 0.6,
            ease: 'expo.out',
            stagger: 0.08,
            scrollTrigger: {
                trigger: '.cta',
                start: 'top 80%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.cta-subtitle', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.cta',
                start: 'top 70%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.cta-btn-primary', {
            opacity: 1,
            scale: 1,
            duration: 0.7,
            ease: 'elastic.out(1, 0.5)',
            scrollTrigger: {
                trigger: '.cta',
                start: 'top 60%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.cta-btn-secondary', {
            opacity: 1,
            duration: 0.5,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.cta',
                start: 'top 55%',
                toggleActions: 'play none none none'
            }
        });
        
        // Footer animations
        gsap.to('#footerBorder', {
            scaleX: 1,
            duration: 0.8,
            ease: 'expo.out',
            scrollTrigger: {
                trigger: '.footer',
                start: 'top 90%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.footer-title', {
            opacity: 1,
            y: 0,
            duration: 0.4,
            ease: 'expo.out',
            stagger: 0.1,
            scrollTrigger: {
                trigger: '.footer-grid',
                start: 'top 85%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.footer-links li', {
            opacity: 1,
            y: 0,
            duration: 0.3,
            ease: 'power2.out',
            stagger: 0.05,
            scrollTrigger: {
                trigger: '.footer-grid',
                start: 'top 80%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.footer-contact p', {
            opacity: 1,
            y: 0,
            duration: 0.4,
            ease: 'expo.out',
            stagger: 0.08,
            scrollTrigger: {
                trigger: '.footer-grid',
                start: 'top 80%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.footer-copyright', {
            opacity: 1,
            duration: 0.4,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.footer-bottom',
                start: 'top 95%',
                toggleActions: 'play none none none'
            }
        });
        
        gsap.to('.footer-social a', {
            opacity: 1,
            scale: 1,
            duration: 0.3,
            ease: 'elastic.out(1, 0.5)',
            stagger: 0.06,
            scrollTrigger: {
                trigger: '.footer-bottom',
                start: 'top 95%',
                toggleActions: 'play none none none'
            }
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
