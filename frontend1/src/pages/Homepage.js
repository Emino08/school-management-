import React from "react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import {
  ArrowRight,
  BookOpen,
  GraduationCap,
  Shield,
  Users,
} from "lucide-react";
import BoSchoolLogo from "@/assets/Bo-School-logo.png";
import BackgroundImage from "@/assets/boSchool.jpg";
import SeoHead from "@/components/SeoHead";

const seoStructuredData = {
  "@context": "https://schema.org",
  "@type": "WebApplication",
  name: "Bo Government Secondary School Management System",
  applicationCategory: "EducationalApplication",
  operatingSystem: "Web",
  url: "https://boschool.org/",
  image: "https://boschool.org/Bo-School-logo.png",
  offers: {
    "@type": "Offer",
    availability: "https://schema.org/InStock",
    price: "0",
    priceCurrency: "USD",
  },
  creator: {
    "@type": "EducationalOrganization",
    name: "Bo Government Secondary School",
    sameAs: ["https://boschool.net/"],
  },
};

const features = [
  {
    icon: Shield,
    title: "Secure",
  },
  {
    icon: Users,
    title: "Unified",
  },
  {
    icon: BookOpen,
    title: "Intelligent",
  },
];

const Homepage = () => {
  return (
    <div className="min-h-screen relative overflow-hidden">
      {/* Background Image with Overlay */}
      <div 
        className="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style={{ backgroundImage: `url(${BackgroundImage})` }}
      >
        <div className="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-900/85 to-purple-900/90 backdrop-blur-[2px]" />
      </div>

      <SeoHead
        title="Bo Government Secondary School | Unified School Management System"
        description="Official Bo Government Secondary School Management System connecting academics, finance, welfare, and guardians across a secure cloud platform."
        keywords="Bo Government Secondary School, Bo School Management System, BOSCHOOL, Sierra Leone education, student information system"
        structuredData={seoStructuredData}
      />

      {/* Main Content */}
      <div className="relative container mx-auto px-6 flex flex-col items-center justify-center min-h-screen">
        {/* Logo */}
        <div className="mb-16 animate-fade-in">
          <img
            src={BoSchoolLogo}
            alt="Bo Government Secondary School"
            className="h-32 w-auto mx-auto drop-shadow-2xl"
          />
        </div>

        {/* Heading */}
        <div className="text-center space-y-6 max-w-3xl mb-16">
          <h1 className="text-5xl md:text-7xl font-light text-white tracking-tight">
            Bo Government Secondary School
          </h1>
          <p className="text-xl md:text-2xl text-slate-200 font-light">
            School Management System
          </p>
          <p className="text-base text-slate-300 max-w-xl mx-auto">
            A unified platform for academics, finance, and student welfare
          </p>
        </div>

        {/* Features */}
        <div className="flex gap-12 mb-16">
          {features.map((feature) => {
            const Icon = feature.icon;
            return (
              <div key={feature.title} className="flex flex-col items-center gap-3">
                <Icon className="h-8 w-8 text-slate-300" strokeWidth={1.5} />
                <span className="text-sm text-slate-200 font-light">{feature.title}</span>
              </div>
            );
          })}
        </div>

        {/* CTA Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 mb-8">
          <Link to="/choose">
            <Button 
              size="lg" 
              className="bg-white hover:bg-slate-100 text-slate-900 px-8 py-6 text-base font-normal rounded-full transition-all duration-300 hover:scale-105 shadow-lg"
            >
              <GraduationCap className="mr-2 h-5 w-5" />
              Access Portal
            </Button>
          </Link>
          <Link to="/check-results">
            <Button 
              variant="outline" 
              size="lg"
              className="border-white/30 bg-white/10 backdrop-blur-sm text-white hover:bg-white/20 px-8 py-6 text-base font-normal rounded-full transition-all duration-300 hover:scale-105"
            >
              Check Results
              <ArrowRight className="ml-2 h-5 w-5" />
            </Button>
          </Link>
        </div>

        {/* Motto */}
        <p className="text-sm uppercase tracking-[0.3em] text-slate-300 font-light mt-12">
          Manners Maketh Man
        </p>
      </div>

      {/* Footer */}
      <footer className="relative py-6 text-center">
        <p className="text-xs text-slate-300">
          Powered by{' '}
          <a
            href="https://sabiteck.com"
            target="_blank"
            rel="noreferrer"
            className="hover:text-white transition-colors"
          >
            Sabiteck
          </a>
        </p>
      </footer>
    </div>
  );
};

export default Homepage;
