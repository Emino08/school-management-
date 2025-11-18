import React from "react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  ArrowRight,
  Award,
  Banknote,
  BookOpenCheck,
  CheckCircle,
  Globe2,
  GraduationCap,
  LineChart,
  MessageCircle,
  Shield,
  Stethoscope,
  Users,
} from "lucide-react";
import Students from "../assets/boSchool.jpg";
import BoSchoolLogo from "@/assets/Bo-School-logo.png";
import SeoHead from "@/components/SeoHead";

const strategyPillars = [
  {
    icon: Shield,
    title: "Security & Compliance",
    description:
      "ISO-aligned controls, audited logs, and per-role authorization keep sensitive records safe.",
  },
  {
    icon: Globe2,
    title: "Unified Experience",
    description:
      "Students, staff, medical, and guardians collaborate through one authenticated workspace.",
  },
  {
    icon: LineChart,
    title: "Insightful Intelligence",
    description:
      "Real-time dashboards for enrollment, attendance, finance, and outcomes drive faster decisions.",
  },
];

const solutions = [
  {
    icon: BookOpenCheck,
    title: "Academic Operations",
    description:
      "Admissions, class placement, assessments, and transcripts orchestrated without spreadsheets.",
  },
  {
    icon: Banknote,
    title: "Finance & Fees",
    description:
      "Automated billing cycles, fee reminders, and receipting keep cashflow predictable.",
  },
  {
    icon: MessageCircle,
    title: "Guardian Engagement",
    description:
      "Smart notifications, result portals, and feedback loops keep families informed daily.",
  },
  {
    icon: Stethoscope,
    title: "Student Welfare",
    description:
      "Medical units capture vitals, care plans, and clearances in sync with academics.",
  },
];

const testimonials = [
  {
    quote:
      "Bo School now has a live view of every learner’s journey. Decision making moved from reactive paperwork to proactive care.",
    role: "Head Teacher, Bo School",
  },
  {
    quote:
      "Finance, admissions, and medical officers finally work from the same source of truth. Parents notice the difference instantly.",
    role: "Operations Lead, Digital Campus Program",
  },
];

const stats = [
  { label: "Academic Journeys", value: "5,000+", detail: "Learners supported every term" },
  { label: "Staff Profiles", value: "450+", detail: "Teachers, examiners, medical teams" },
  { label: "Automations", value: "120+", detail: "Daily tasks & workflows digitized" },
];

const seoStructuredData = {
  "@context": "https://schema.org",
  "@type": "WebApplication",
  name: "Bo School Management System",
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
    name: "Bo School",
    sameAs: ["https://boschool.net/"],
  },
};

const Homepage = () => {
  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <SeoHead
        title="Bo School | Unified School Management System"
        description="Official Bo School Management System connecting academics, finance, welfare, and guardians across a secure cloud platform."
        keywords="Bo School, Bo School Management System, BOSCHOOL, Sierra Leone education, student information system"
        structuredData={seoStructuredData}
      />

      {/* Hero Section */}
      <section className="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-900 to-purple-900 text-white">
        <div className="pointer-events-none absolute inset-0 opacity-30">
          <div className="absolute -right-20 top-0 h-96 w-96 rounded-full bg-purple-600 blur-3xl" />
          <div className="absolute bottom-0 left-0 h-80 w-80 rounded-full bg-cyan-500 blur-3xl" />
        </div>

        <div className="relative container mx-auto grid grid-cols-1 gap-12 px-6 py-20 lg:grid-cols-2 lg:items-center">
          <div className="space-y-8">
            <Badge className="bg-white/10 text-white backdrop-blur border border-white/30">
              Official Bo School Platform
            </Badge>

            <div>
              <p className="text-sm uppercase tracking-[0.3em] text-slate-300">
                Digital Campus Intelligence
              </p>
              <h1 className="mt-4 text-4xl font-semibold leading-tight md:text-5xl xl:text-6xl">
                Manage every academic and welfare moment from one secure control
                center.
              </h1>
            </div>

            <p className="text-lg text-slate-200">
              Admissions, payments, attendance, medical briefs, and family
              communication now stay in sync. Built exclusively for Bo School’s
              rigorous standards and community.
            </p>

            <div className="grid gap-4 text-sm text-slate-200 md:grid-cols-2">
              {[
                "Role-aware dashboards for admin, teachers, parents, and medical teams",
                "Realtime analytics highlight trends across exams, welfare, and fees",
                "Secure APIs connect backend.boschool.org with boschool.org front-end",
              ].map((item) => (
                <p key={item} className="flex items-start gap-2">
                  <CheckCircle className="mt-1 h-5 w-5 text-emerald-400" />
                  {item}
                </p>
              ))}
            </div>

            <div className="flex flex-col gap-4 sm:flex-row">
              <Link to="/choose" className="flex-1">
                <Button className="w-full bg-emerald-400 text-slate-900 hover:bg-emerald-300" size="lg">
                  <GraduationCap className="mr-2 h-5 w-5" />
                  Sign in to your portal
                </Button>
              </Link>
              <Link to="/check-results" className="flex-1">
                <Button variant="outline" size="lg" className="w-full border-white/40 text-black hover:bg-white/10 hover:text-white">
                  Check public results
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Button>
              </Link>
            </div>

            <p className="text-sm text-slate-300">
              Need onboarding?{" "}
              <Link to="/chooseasguest" className="underline underline-offset-4">
                Explore the experience as a guest
              </Link>
            </p>
          </div>

          <div className="relative">
            <div className="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
              <img
                src={Students}
                className="h-72 w-full rounded-2xl object-cover shadow-2xl"
                alt="Bo School students collaborating"
                loading="lazy"
              />
              <div className="mt-6 grid gap-4 md:grid-cols-2">
                {stats.map((stat) => (
                  <Card key={stat.label} className="bg-white/10 text-white border-white/20">
                    <CardHeader className="py-4">
                      <CardTitle className="text-3xl font-bold">{stat.value}</CardTitle>
                      <CardDescription className="text-slate-200">{stat.label}</CardDescription>
                      <p className="text-xs text-slate-300">{stat.detail}</p>
                    </CardHeader>
                  </Card>
                ))}
              </div>
            </div>
          </div>

          <div className="relative flex items-center justify-center lg:justify-end">
            <div className="relative flex flex-col items-center">
              <div className="absolute h-64 w-64 rounded-full bg-white/20 blur-3xl" />
              <img
                src={BoSchoolLogo}
                alt="Bo School crest"
                className="relative z-10 h-64 w-auto drop-shadow-[0_25px_45px_rgba(0,0,0,0.5)]"
              />
              <p className="mt-4 text-sm uppercase tracking-[0.5em] text-amber-300">
                Manners Maketh Man
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Pillars */}
      <section className="container mx-auto px-6 py-16">
        <div className="mx-auto text-center md:w-2/3">
          <Badge className="bg-emerald-100 text-emerald-700">Strategic Pillars</Badge>
          <h2 className="mt-4 text-3xl font-semibold text-slate-900">
            Purpose-built for Bo School’s heritage and future.
          </h2>
          <p className="mt-4 text-slate-600">
            The platform evolved through years of digitizing admissions, exam offices,
            and medical units at Bo School. Each pillar keeps humans at the center.
          </p>
        </div>

        <div className="mt-12 grid gap-6 md:grid-cols-3">
          {strategyPillars.map((pillar) => {
            const Icon = pillar.icon;
            return (
              <Card key={pillar.title} className="shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <CardHeader>
                  <div className="flex items-center gap-3">
                    <span className="rounded-full bg-emerald-50 p-3 text-emerald-600">
                      <Icon className="h-5 w-5" />
                    </span>
                    <CardTitle>{pillar.title}</CardTitle>
                  </div>
                  <CardDescription className="pt-3 text-slate-600">{pillar.description}</CardDescription>
                </CardHeader>
              </Card>
            );
          })}
        </div>
      </section>

      {/* Solutions Grid */}
      <section className="bg-white py-16">
        <div className="container mx-auto px-6">
          <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div className="space-y-6">
              <Badge className="bg-slate-900 text-white">Unified Command Center</Badge>
              <h2 className="text-3xl font-semibold text-slate-900">
                Every stakeholder sees what they need—and only what they need.
              </h2>
              <p className="text-slate-600">
                backend.boschool.org exposes secure APIs while boschool.org delivers a polished,
                mobile-ready interface. Deep linking keeps the experience fast, intuitive, and private.
              </p>

              <div className="grid gap-4 md:grid-cols-2">
                {solutions.map((solution) => {
                  const Icon = solution.icon;
                  return (
                    <Card key={solution.title} className="border-slate-100 shadow-none">
                      <CardHeader className="flex flex-row items-start gap-3">
                        <span className="rounded-2xl bg-slate-50 p-3 text-slate-900 shadow-sm">
                          <Icon className="h-5 w-5" />
                        </span>
                        <div>
                          <CardTitle className="text-base">{solution.title}</CardTitle>
                          <CardDescription className="pt-2 text-slate-600">{solution.description}</CardDescription>
                        </div>
                      </CardHeader>
                    </Card>
                  );
                })}
              </div>
            </div>

            <Card className="bg-slate-900 text-white">
              <CardHeader>
                <Badge className="bg-white/10 text-white">Quality Signals</Badge>
                <CardTitle className="text-2xl">Trusted by the Bo School community</CardTitle>
                <CardDescription className="text-slate-200">
                  We continuously co-create with education leaders, alumni, and guardians.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                {testimonials.map((item) => (
                  <div key={item.role} className="rounded-2xl border border-white/20 p-4">
                    <p className="text-sm text-slate-100">“{item.quote}”</p>
                    <p className="mt-3 text-xs uppercase tracking-widest text-slate-300">{item.role}</p>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      <footer className="bg-slate-900 py-8 text-center text-white">
        <p className="text-sm">
          Powered by{' '}
          <a
            href="https://sabiteck.com"
            target="_blank"
            rel="noreferrer"
            className="underline decoration-dotted underline-offset-4"
          >
            Sabiteck
          </a>
          . All rights reserved.
        </p>
      </footer>
    </div>
  );
};

export default Homepage;
